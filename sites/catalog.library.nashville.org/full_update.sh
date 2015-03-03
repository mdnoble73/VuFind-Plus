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

# Prohibited time ranges - for, e.g., ILS backup
# JAMES is currently giving all Nashville prohibited times a ten minute buffer
function checkProhibitedTimes() {
	start=$(date --date=$1 +%s)
	stop=$(date --date=$2 +%s)
	NOW=$(date +%H:%M:%S)
	NOW=$(date --date=$NOW +%s)

	hasConflicts=0
	if (( $start < $stop ))
	then
		if (( $NOW > $start && $NOW < $stop ))
		then
			#echo "Sleeping:" $(($stop - $NOW))
			sleep $(($stop - $NOW))
			hasConflicts = 1
		fi
	elif (( $start > $stop ))
	then
		if (( $NOW < $stop ))
		then
			sleep $(($stop - $NOW))
			hasConflicts = 1
		elif (( $NOW > $start ))
		then
			sleep $(($stop + 86400 - $NOW))
			hasConflicts = 1
		fi
	fi
	echo ${hasConflicts};
}

#First make sure that we aren't running at a bad time.  This is really here in case we run manually.
# since the run in cron is timed to avoid sensitive times.

# Bib Extract for Galacto
checkProhibitedTimes "04:15" "04:50"
# Millennium Extract for Catalog
checkProhibitedTimes "05:15" "06:00"
# Millennium Extract for Catalog
checkProhibitedTimes "09:15" "10:00"
# Millennium Extract for Catalog
checkProhibitedTimes "13:15" "14:00"
# Millennium Extract for Catalog
checkProhibitedTimes "17:15" "18:00"
# Millennium Extract for Catalog
checkProhibitedTimes "21:15" "22:00"

#Check for any conflicting processes that we shouldn't do a full index during.
#Since we aren't running in a loop, check in the order they run.
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

#Extract Lexile Data
cd /data/vufind-plus/; rm lexileTitles.txt*; wget http://venus.marmot.org/lexileTitles.txt

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

