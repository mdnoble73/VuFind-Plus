#!/bin/bash
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day.  Will interrupt partial reindexing.
#
# At the end of the index will email users with the results.
EMAIL=root@titan
PIKASERVER=marmot.test
PIKADBNAME=pika
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

# Check if full_update is already running
#TODO: Verify that the PID file doesn't get log-rotated
PIDFILE="/var/log/vufind-plus/${PIKASERVER}/full_update.pid"
if [ -f $PIDFILE ]
then
	PID=$(cat $PIDFILE)
	ps -p $PID > /dev/null 2>&1
	if [ $? -eq 0 ]
	then
		mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL <<< "$0 is already running"
		exit 1
	else
		## Process not found assume not running
		echo $$ > $PIDFILE
		if [ $? -ne 0 ]
		then
			mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL <<< "Could not create PID file for $0"
			exit 1
		fi
	fi
else
	echo $$ > $PIDFILE
	if [ $? -ne 0 ]
	then
		mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL <<< "Could not create PID file for $0"
		exit 1
	fi
fi

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
checkConflictingProcesses "sierra_export.jar ${PIKASERVER}" >> ${OUTPUT_FILE}
checkConflictingProcesses "overdrive_extract.jar ${PIKASERVER}" >> ${OUTPUT_FILE}
checkConflictingProcesses "reindexer.jar ${PIKASERVER}" >> ${OUTPUT_FILE}

#truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

# Back-up Solr Master Index
mysqldump ${PIKADBNAME} grouped_work_primary_identifiers > /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql
sleep 2m
tar -czf /data/vufind-plus/${PIKASERVER}/solr_master_backup.tar.gz /data/vufind-plus/${PIKASERVER}/solr_master/grouped/index/ /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql >> ${OUTPUT_FILE}
rm /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
#/usr/local/vufind-plus/sites/${PIKASERVER}/copySierraExport.sh >> ${OUTPUT_FILE}
# Moved to crontab so that cassini crontask will always have the lastest export for their processes.

#Extract from Hoopla
#cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}
# Grab manually from Nashville, after James does Marc Clean up work.  pascal 6-7-2016

# Ebrary Marc Updates
#TODO: refactor CCU's ebrary destination
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh ccu_ebrary ebrary_ccu >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh adams/ebrary ebrary/adams >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh western/ebrary ebrary/western >> ${OUTPUT_FILE}

#Adams Ebrary DDA files
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh adams/ebrary/DDA ebrary/adams/dda/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh ebrary/adams/dda >> ${OUTPUT_FILE}

# CCU Ebsco Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh ebsco_ccu ebsco/ccu >> ${OUTPUT_FILE}

# CMC Ebsco Academic Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh cmc/ebsco ebsco/cmc >> ${OUTPUT_FILE}

# Fort Lewis Ebsco Academic Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh fortlewis_sideload/EBSCO_Academic ebsco/fortlewis/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh fortlewis_sideload/EBSCO_Academic/deletes ebsco/fortlewis/deletes >> ${OUTPUT_FILE}

# Western Oxford Reference Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh western/oxfordReference oxfordReference/western >> ${OUTPUT_FILE}

# Western Springer Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh western/springer springer/western >> ${OUTPUT_FILE}

# Western Kanopy Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh western/kanopy kanopy/western >> ${OUTPUT_FILE}

# SD51 Mackin VIA Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvcp mackinvia/mvcp >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvem mackinvia/mvem >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvrr mackinvia/mvrr >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvtm mackinvia/mvtm >> ${OUTPUT_FILE}

# Learning Express Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh budwerner/learning_express learning_express/steamboatsprings/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh budwerner/learning_express/deletes learning_express/steamboatsprings/deletes >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh garfield/learning_express learning_express/garfield/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh garfield/learning_express/deletes learning_express/garfield/deletes >> ${OUTPUT_FILE}

# TODO: set up actual ftp update paths
#/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh {ftpdir} learning_express/garfield >> ${OUTPUT_FILE}
#/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh {ftpdir} learning_express/vail >> ${OUTPUT_FILE}

# OneClick digital Marc Updates
#/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh englewood/oneclickdigital oneclickdigital/englewood >> ${OUTPUT_FILE}

# Colorado State Gov Docs Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh cologovdocs colorado_gov_docs >> ${OUTPUT_FILE}

# Lynda.com Marc Updates (recieved on marmot ftp server)
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh lynda.com/evld lynda/evld/merge
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh lynda.com/vail lynda/vail/merge
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh lynda.com/telluride lynda/telluride/merge

# Merge OneClick digital Records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh oneclickdigital/englewood >> ${OUTPUT_FILE}


#Extracts for sideloaded eContent; settings defined in config.pwd.ini [Sideload]
cd /usr/local/vufind-plus/vufind/cron; ./sideload.sh ${PIKASERVER}

# Merge Learning Express Records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh learning_express/steamboatsprings >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh learning_express/garfield >> ${OUTPUT_FILE}

# Merge Lynda.com Records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh lynda/evld >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh lynda/vail >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh lynda/telluride >> ${OUTPUT_FILE}

#Merge EBSCO records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh ebsco/fortlewis >> ${OUTPUT_FILE}


#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
DAYOFWEEK=$(date +"%u")
if [ "${DAYOFWEEK}" -eq 5 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

#Note, no need to extract from Lexile for this server since it is the master

#Validate the export
cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -XX:+UseG1GC -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#TODO: Determine if we should do a partial update from the ILS and OverDrive before running the reindex to grab last minute changes

#Full Reindex
cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

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

# Now that script is completed, remove the PID file
rm $PIDFILE

