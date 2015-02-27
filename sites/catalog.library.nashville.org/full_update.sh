#!/bin/bash

# full_update.sh
# Mark Noble, Marmot Library Network
# James Staub, Nashville Public Library
# 20150219
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day from crontab
# For Pika discovery partners using Millennium 2011 1.6_3

# this version emails script output as a round finishes
EMAIL=james.staub@nashville.gov
ILSSERVER=waldo.library.nashville.org
PIKASERVER=catalog.library.nashville.org
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
# commented out cause expect is outputting way too much
#cd /usr/local/VuFind-Plus/vufind/millennium_export/; expect BIB_HOLDS_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER} >> ${OUTPUT_FILE}
# commented out during dev to ensure production file does not get crowded out
#cd /usr/local/VuFind-Plus/vufind/millennium_export/; expect BIB_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER} >> ${OUTPUT_FILE}

#Extract from Hoopla
cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}

#Note, no need to extract from OverDrive since it happens continuously
#Note, no need to extract from Lexile for this server since it is the master

# should test for new bib extract file
# should copy old bib extract file

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -Xmx6G -XX:+UseParallelGC -XX:ParallelGCThreads=2 -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#Full Reindex
cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
# send mail
mail -s "Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi

