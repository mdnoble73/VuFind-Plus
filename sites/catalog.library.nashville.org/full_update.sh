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

# Check for conflicting processes currently running
function checkConflictingProcesses() {
	#Check to see if the conflict exists.
	countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
	countConflictingProcesses=$((countConflictingProcesses-1))

	let numInitialConflicts=countConflictingProcesses
	#Wait until the conflict is gone.
	until ((${countConflictingProcesses} == 0)); do
		countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
		countConflictingProcesses=$((countConflictingProcesses-1))
		#echo "Count of conflicting process" $1 $countConflictingProcesses
		sleep 300
	done
	#Return the number of conflicts we found initially.
	echo ${numInitialConflicts};
}

#Check for any conflicting processes that we shouldn't do a full index during.
checkConflictingProcesses "ITEM_UPDATE_EXTRACT_PIKA.exp ${PIKASERVER}"
checkConflictingProcesses "millennium_export.jar ${PIKASERVER}"
checkConflictingProcesses "overdrive_extract.jar ${PIKASERVER}"
checkConflictingProcesses "reindexer.jar ${PIKASERVER}"


#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
cd /usr/local/VuFind-Plus/vufind/millennium_export/; expect BIB_HOLDS_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER} >> ${OUTPUT_FILE}
cd /usr/local/VuFind-Plus/vufind/millennium_export/; expect BIB_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER} >> ${OUTPUT_FILE}

#Extract from Hoopla
cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}

#Note: should not need OverDrive call, since it happens in continuous_partial_reindex.sh and a full overdrive pull can take 6 hours or more
#Note, no need to extract from Lexile for this server since it is the master

# should test for new bib extract file
# should copy old bib extract file

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -Xmx6G -XX:+UseParallelGC -XX:ParallelGCThreads=2 -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#Full Reindex
cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

#Remove all ITEM_UPDATE_EXTRACT_PIKA files so continuous_partial_reindex can start fresh
find /data/vufind-plus/catalog.library.nashville.org/marc -name 'ITEM_UPDATE_EXTRACT_PIKA*' -delete

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
# send mail
mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi

