#!/bin/bash

# full_update.sh
# Mark Noble, Marmot Library Network
# James Staub, Nashville Public Library
# 20150219
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day from crontab
# For Pika discovery partners using Millennium 2011 1.6_3

# this version emails script output as a round finishes
EMAIL=mark@marmot.org,pascal@marmot.org
ILSSERVER=nell.boulderlibrary.org
PIKASERVER=flatirons.test
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
# Flatirons has no prohibited times (yet)
#checkProhibitedTimes "23:50" "00:40"

#Check for any conflicting processes that we shouldn't do a full index during.
#Since we aren't running in a loop, check in the order they run.
#checkConflictingProcesses "ITEM_UPDATE_EXTRACT_PIKA_4_Flatirons.exp"
#checkConflictingProcesses "millennium_export.jar"
checkConflictingProcesses "overdrive_extract.jar flatirons.test"
checkConflictingProcesses "reindexer.jar flatirons.test"

#truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
#copy extracts from production servers
#TODO use scp to copy records from flatirons production server or have them pushed to the test server
#cd /data/vufind-plus/flatirons.test/marc
#wget -N --no-verbose http://flc.flatironslibrary.org/BIB_EXTRACT_PIKA.MRC
#wget -N --no-verbose http://flc.flatironslibrary.org/BIB_HOLDS_EXTRACT_PIKA.TXT
# --no-verbose Turn off verbose without being completely quiet (use -q for that), which means that error messages and basic information still get printed.

#Extract from Hoopla
#No need to copy on marmot test server
#cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}

#Extract Lexile Data
#No need to copy on marmot test server
#cd /data/vufind-plus/; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/lexileTitles.txt http://venus.marmot.org/lexileTitles.txt

#Extract AR Data
#No need to copy on marmot test server
#cd /data/vufind-plus/accelerated_reader; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/accelerated_reader/RLI-ARDataTAB.txt http://venus.marmot.org/RLI-ARDataTAB.txt


#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
DAYOFWEEK=$(date +"%u")
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

# should test for new bib extract file
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh flatirons_marc_export flatirons.test >> ${OUTPUT_FILE}

# should copy old bib extract file

#Validate the export
cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -XX:+UseG1GC -Xmx6G -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#Full Reindex
cd /usr/local/vufind-plus/vufind/reindexer; java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

#Remove all ITEM_UPDATE_EXTRACT_PIKA files so continuous_partial_reindex can start fresh
find /data/vufind-plus/${PIKASERVER}/marc -name 'ITEM_UPDATE_EXTRACT_PIKA*' -delete

# Only needed once on venus
# Clean-up Solr Logs
#find /usr/local/vufind-plus/sites/default/solr/jetty/logs -name "solr_log_*" -mtime +7 -delete
#find /usr/local/vufind-plus/sites/default/solr/jetty/logs -name "solr_gc_log_*" -mtime +7 -delete

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
# send mail
mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi

