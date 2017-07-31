#!/bin/bash

# full_update.sh
# Mark Noble, Marmot Library Network
# James Staub, Nashville Public Library
# 20150219
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day from crontab

# this version emails script output
EMAIL=mark@marmot.org,pascal@marmot.org
ILSSERVER=nell.boulderlibrary.org
PIKASERVER=flatirons.production
PIKADBNAME=pika
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

MINFILE1SIZE=$((1407000000))

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
checkConflictingProcesses "sierra_export.jar" >> ${OUTPUT_FILE}
checkConflictingProcesses "overdrive_extract.jar" >> ${OUTPUT_FILE}
checkConflictingProcesses "reindexer.jar" >> ${OUTPUT_FILE}

#truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

# Back-up Solr Master Index
mysqldump ${PIKADBNAME} grouped_work_primary_identifiers > /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql
sleep 2m
tar -czf /data/vufind-plus/${PIKASERVER}/solr_master_backup.tar.gz /data/vufind-plus/${PIKASERVER}/solr_master/grouped/index/ /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql >> ${OUTPUT_FILE}
rm /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from Hoopla
#cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}
cd /usr/local/vufind-plus/vufind/cron;./GetHooplaFromMarmot.sh >> ${OUTPUT_FILE}

#Extract Lexile Data
cd /data/vufind-plus/; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/lexileTitles.txt https://cassini.marmot.org/lexileTitles.txt >> ${OUTPUT_FILE}

#Extract AR Data
#cd /data/vufind-plus/accelerated_reader; wget -N --no-verbose https://cassini.marmot.org/RLI-ARDataTAB.txt
cd /data/vufind-plus/accelerated_reader; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/accelerated_reader/RLI-ARDataTAB.txt https://cassini.marmot.org/RLI-ARDataTAB.txt >> ${OUTPUT_FILE}

#Zinio Marc Updates
scp flatirons_sideload@ftp1.marmot.org:/ftp/flatirons_sideload/zinio/shared/*.mrc /data/vufind-plus/zinio/boulderBroomfield/marc/ >> ${OUTPUT_FILE}

#Ebrary Marc Updates
scp flatirons_sideload@ftp1.marmot.org:/ftp/flatirons_sideload/ebrary/boulder/*.mrc /data/vufind-plus/ebrary/bpl/marc/merge >> ${OUTPUT_FILE}
scp flatirons_sideload@ftp1.marmot.org:/ftp/flatirons_sideload/ebrary/boulder/deletes.*.mrc /data/vufind-plus/ebrary/bpl/deletes/marc/ >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh ebrary/bpl >> ${OUTPUT_FILE}

scp flatirons_sideload@ftp1.marmot.org:/ftp/flatirons_sideload/ebrary/broomfield/*.mrc /data/vufind-plus/ebrary/mde/marc/ >> ${OUTPUT_FILE}

# Possible curl version; if I can figure out how to implement the --time-condition check on a range of files
# (Can't do *.mrc; have to specify a range of files that curl will check for each one in the range)
#curl --verbose --remote-name --remote-time --compressed --pubkey ~/.ssh/id_rsa.pub --key ~/.ssh/id_rsa sftp://flatirons_sideload@ftp1.marmot.org:22//ftp/flatirons_sideload/ebrary/boulder/Zinio_boulderco_1619_Magazine_[1-12]_[1-31]_[2016-2017].mrc

#OneClick Digit Marc Updates
scp flatirons_sideload@ftp1.marmot.org:/ftp/flatirons_sideload/oneclickdigital/longmont/*.mrc /data/vufind-plus/oneclickdigital/longmont/marc/ >> ${OUTPUT_FILE}
scp flatirons_sideload@ftp1.marmot.org:/ftp/flatirons_sideload/oneclickdigital/loveland/*.mrc /data/vufind-plus/oneclickdigital/loveland/marc/ >> ${OUTPUT_FILE}

#Colorado State Goverment Documents Updates
curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/colorado_gov_docs/marc/fullexport.mrc https://cassini.marmot.org/colorado_state_docs.mrc

#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
DAYOFWEEK=$(date +"%u")
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

# should test for new bib extract file
# should copy old bib extract file

# Extract from ILS #

# Date For Backup filename
TODAY=$(date +"%m_%d_%Y")

FILE=$(find /home/sierraftp/ -name script.MARC.* -mtime -1 | sort -n | tail -1)

if [ -n "$FILE" ]
then
  #check file size
	FILE1SIZE=$(wc -c <"$FILE")
	if [ $FILE1SIZE -ge $MINFILE1SIZE ]; then

		echo "Latest export file is " $FILE >> ${OUTPUT_FILE}
		DIFF=$(($FILE1SIZE - $MINFILE1SIZE))
		PERCENTABOVE=$((100 * $DIFF / $MINFILE1SIZE))
		echo "The export file is $PERCENTABOVE (%) larger than the minimum size check." >> ${OUTPUT_FILE}

		# Copy to data directory to process
		cp $FILE /data/vufind-plus/flatirons.production/marc/pika1.mrc
		# Move to marc_export to keep as a backup
		mv $FILE /data/vufind-plus/flatirons.production/marc_export/pika.$TODAY.mrc


		#Validate the export
		cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

		#Full Regroup
		cd /usr/local/vufind-plus/vufind/record_grouping; java -server -Xmx6G -XX:+UseG1GC -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

		#Full Reindex
		cd /usr/local/vufind-plus/vufind/reindexer; java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

		# Truncate Continous Reindexing list of changed items
		cat /dev/null >| /data/vufind-plus/${PIKASERVER}/marc/changed_items_to_process.csv

		#Send Export to Marmot for the test server
		scp -q /data/vufind-plus/flatirons.production/marc/pika1.mrc flatirons_marc_export@sftp.marmot.org:~/ >> ${OUTPUT_FILE}

		# Delete any exports over 7 days
		find /data/vufind-plus/flatirons.production/marc_export/ -mindepth 1 -maxdepth 1 -name *.mrc -type f -mtime +7 -delete

	else
		echo $FILE " size " $FILE1SIZE "is less than minimum size :" $MINFILE1SIZE "; Export was not moved to data directory, Full Regrouping & Full Reindexing skipped." >> ${OUTPUT_FILE}
	fi
else
	echo "Did not find a Sierra export file from the last 24 hours, Full Regrouping & Full Reindexing skipped." >> ${OUTPUT_FILE}
fi

# Clean-up Solr Logs
find /usr/local/vufind-plus/sites/default/solr/jetty/logs -name "solr_log_*" -mtime +7 -delete
find /usr/local/vufind-plus/sites/default/solr/jetty/logs -name "solr_gc_log_*" -mtime +7 -delete

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
	# send mail
	mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi

