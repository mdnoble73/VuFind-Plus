#!/bin/bash
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day.  Will interrupt partial reindexing.
#
# At the end of the index will email users with the results.
EMAIL=root@mercury
PIKASERVER=aspencat.production
PIKADBNAME=aspencat_pika
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

MINFILE1SIZE=$((1009000000))

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

#truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

#Check for any conflicting processes that we shouldn't do a full index during.
checkConflictingProcesses "koha_export.jar ${PIKASERVER}"
checkConflictingProcesses "overdrive_extract.jar ${PIKASERVER}"
checkConflictingProcesses "reindexer.jar ${PIKASERVER}"

# Back-up Solr Master Index
mysqldump ${PIKADBNAME} grouped_work_primary_identifiers > /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql
sleep 5m
tar -czf /data2/pika/${PIKASERVER}/solr_master_backup.tar.gz /data/vufind-plus/${PIKASERVER}/solr_master/grouped/index/ >> ${OUTPUT_FILE}
rm /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

# Copy Export from ILS
/usr/local/vufind-plus/sites/${PIKASERVER}/copyExport.sh >> ${OUTPUT_FILE}
# merge files together after the export is copied
cd /usr/local/vufind-plus/vufind/cron/; java -jar cron.jar ${PIKASERVER} MergeMarcUpdatesAndDeletes >> ${OUTPUT_FILE}


#Extract from Hoopla
# No Aspencat libraries use hoopla, no need to copy them
# cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}

#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
DAYOFWEEK=$(date +"%u")
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

#Note, no need to extract from Lexile for this server since it is done by Marmot production extract

#Note, no need to extract from Accelerated Reader for this server since it is done by Marmot production extract

FILE=$(find /data/vufind-plus/${PIKASERVER}/marc/ -name fullexport.mrc -mtime -1 | sort -n | tail -1)
if [ -n "$FILE" ]; then
  #check file size
	FILE1SIZE=$(wc -c <"$FILE")
	if [ $FILE1SIZE -ge $MINFILE1SIZE ]; then
		YESTERDAY=`date +%Y%m%d --date="yesterday"`
		UPDATEFILE=/data/vufind-plus/${PIKASERVER}/marc_backup/ascc-catalog-deleted.$YESTERDAY.marc
		DELETEFILE=/data/vufind-plus/${PIKASERVER}/marc_backup/ascc-catalog-updated.$YESTERDAY.marc
		if [ ! -f $UPDATEFILE ]; then
		 echo "Update File $UPDATEFILE was not found."
		fi
		if [ ! -f $DELETEFILE ]; then
		 echo "Delete File $DELETEFILE was not found."
		fi

		echo "Latest full export file is " $FILE >> ${OUTPUT_FILE}
		DIFF=$(($FILE1SIZE - $MINFILE1SIZE))
		PERCENTABOVE=$((100 * $DIFF / $MINFILE1SIZE))
		echo "The export file is $PERCENTABOVE (%) larger than the minimum size check." >> ${OUTPUT_FILE}

		#Validate the export
		cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

		#Full Regroup
		cd /usr/local/vufind-plus/vufind/record_grouping; java -server -Xmx6G -XX:+UseG1GC -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

		#TODO: Determine if we should do a partial update from the ILS and OverDrive before running the reindex to grab last minute changes

		#Full Reindex
		cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

			# Delete any exports over 7 days
			find /data/vufind-plus/${PIKASERVER}/marc_backup/ -mindepth 1 -maxdepth 1 -name *.marc -type f -mtime +7 -delete

	else
		echo $FILE " size " $FILE1SIZE "is less than minimum size :" $MINFILE1SIZE "; Export was not moved to data directory, Full Regrouping & Full Reindexing skipped." >> ${OUTPUT_FILE}
	fi
else
	echo "Did not find a export file from the last 24 hours, Full Regrouping & Full Reindexing skipped." >> ${OUTPUT_FILE}
fi

# Only needed once on mercury
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

