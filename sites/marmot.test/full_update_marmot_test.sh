#!/bin/bash
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day.  Will interrupt partial reindexing.
#
# At the end of the index will email users with the results.
EMAIL=root@venus
PIKASERVER=marmot.test
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
/root/cron/copySierraExport.sh >> ${OUTPUT_FILE}

#Extract from Hoopla
cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}

#Note, no need to extract from OverDrive since it happens continuously
#Note, no need to extract from Lexile for this server since it is the master

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -Xmx6G -XX:+UseParallelGC -XX:ParallelGCThreads=2 -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#TODO: Determine if we should do a partial update from the ILS and OverDrive before running the reindex to grab last minute changes

#Full Reindex
cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
	# send mail
	mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi