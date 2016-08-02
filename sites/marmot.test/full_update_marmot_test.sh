#!/bin/bash
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day.  Will interrupt partial reindexing.
#
# At the end of the index will email users with the results.
EMAIL=root@venus
PIKASERVER=marmot.test
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

#truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

# Check if full_update is already running
#TODO: Verify that the PID file doesn't get log-rotated
PIDFILE="/var/log/vufind-plus/${PIKASERVER}/full_update.pid"
if [ -f $PIDFILE ]
then
	PID=$(cat $PIDFILE)
	ps -p $PID > /dev/null 2>&1
	if [ $? -eq 0 ]
	then
		echo "$0 is already running"  >> ${OUTPUT_FILE}
		mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
		exit 1
	else
		## Process not found assume not running
		echo $$ > $PIDFILE
		if [ $? -ne 0 ]
		then
			echo "Could not create PID file for $0" >> ${OUTPUT_FILE}
			mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
			exit 1
		fi
	fi
else
	echo $$ > $PIDFILE
	if [ $? -ne 0 ]
	then
		echo "Could not create PID file for $0" >> ${OUTPUT_FILE}
		mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
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
checkConflictingProcesses "sierra_export.jar ${PIKASERVER}"
checkConflictingProcesses "overdrive_extract.jar ${PIKASERVER}"
checkConflictingProcesses "reindexer.jar ${PIKASERVER}"

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
/root/cron/copySierraExport.sh >> ${OUTPUT_FILE}

#Extract from Hoopla
#cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}
# Grab manually from Nashville, after James does Marc Clean up work.  pascal 6-7-2016

# Ebrary Marc Updates
#TODO: refactor CCU's ebrary destination
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh ccu_ebrary ebrary_ccu >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh adams/ebrary ebrary/adams >> ${OUTPUT_FILE}

# CCU Ebsco Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh ebsco_ccu ebsco/ccu >> ${OUTPUT_FILE}

# CCU Ebsco Academic Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh cmc/ebsco ebsco/cmc >> ${OUTPUT_FILE}

# SD51 Mackin VIA Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvcp mackinvia/mvcp >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvem mackinvia/mvem >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvrr mackinvia/mvrr >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh mcvsd/mackinvia/mvtm mackinvia/mvtm >> ${OUTPUT_FILE}

# Learning Express Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh budwerner/learning_express learning_express/steamboatsprings >> ${OUTPUT_FILE}
# TODO: set up actual ftp update paths
#/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh {ftpdir} learning_express/garfield >> ${OUTPUT_FILE}
#/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh {ftpdir} learning_express/vail >> ${OUTPUT_FILE}

# OneClick digital Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh englewood/oneclickdigital oneclickdigital/englewood >> ${OUTPUT_FILE}

# Colorado State Gov Docs Marc Updates
/usr/local/vufind-plus/sites/marmot.test/moveFullExport.sh cologovdocs colorado_gov_docs >> ${OUTPUT_FILE}

#Lynda.com Marc Updates
# (EVLD, Vail)
#Extracts for sideloaded eContent; settings defined in config.pwd.ini [Sideload]
cd /usr/local/vufind-plus/vufind/cron; ./sideload.sh ${PIKASERVER}


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

