#!/bin/bash
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day.  Will interrupt partial reindexing.
#
# At the end of the index will email users with the results.
EMAIL=root@mercury
PIKASERVER=opac.marmot.org
PIKADBNAME=vufind
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

MINFILE1SIZE=$((4230000000))

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
checkConflictingProcesses "sierra_export.jar ${PIKASERVER}" >> ${OUTPUT_FILE}
checkConflictingProcesses "overdrive_extract.jar ${PIKASERVER}" >> ${OUTPUT_FILE}
checkConflictingProcesses "reindexer.jar ${PIKASERVER}" >> ${OUTPUT_FILE}

# Back-up Solr Master Index
mysqldump ${PIKADBNAME} grouped_work_primary_identifiers > /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql
sleep 6m
tar -czf /data2/pika/${PIKASERVER}/solr_master_backup.tar.gz /data/vufind-plus/${PIKASERVER}/solr_master/grouped/index/  /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql >> ${OUTPUT_FILE}
rm /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
/root/cron/copySierraExport.sh >> ${OUTPUT_FILE}

#Extract from Hoopla
#cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}
cd /usr/local/vufind-plus/vufind/cron;./GetHooplaFromMarmot.sh >> ${OUTPUT_FILE}
# Grab cleaned, updated marc files from venus after they have been retrieved from Nashville

# Ebrary Marc Updates
#TODO: refactor CCU's ebrary destination
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh ccu_ebrary ebrary_ccu >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh adams/ebrary ebrary/adams >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/ebrary ebrary/western >> ${OUTPUT_FILE}

#Adams Ebrary DDA files
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh adams/ebrary/DDA ebrary/adams/dda/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh ebrary/adams/dda >> ${OUTPUT_FILE}

# CCU Ebsco Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh ebsco_ccu ebsco/ccu >> ${OUTPUT_FILE}

# CMC Ebsco Academic Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh cmc/ebsco ebsco/cmc >> ${OUTPUT_FILE}

# Fort Lewis Ebsco Academic Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh fortlewis_sideload/EBSCO_Academic ebsco/fortlewis/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh fortlewis_sideload/EBSCO_Academic/deletes ebsco/fortlewis/deletes >> ${OUTPUT_FILE}

# Western Oxford Reference Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/oxfordReference oxfordReference/western >> ${OUTPUT_FILE}

# Western Springer Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/springer springer/western >> ${OUTPUT_FILE}

# Western Kanopy Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/kanopy kanopy/western >> ${OUTPUT_FILE}

# Learning Express Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh budwerner/learning_express learning_express/steamboatsprings/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh budwerner/learning_express/deletes learning_express/steamboatsprings/deletes >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh garfield/learning_express learning_express/garfield/merge >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh garfield/learning_express/deletes learning_express/garfield/deletes >> ${OUTPUT_FILE}

# OneClick digital Marc Updates
#/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh englewood/oneclickdigital oneclickdigital/englewood >> ${OUTPUT_FILE}


# Colorado State Gov Docs Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh cologovdocs colorado_gov_docs >> ${OUTPUT_FILE}

# Lynda.com Marc Updates (recieved on marmot ftp server)
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh lynda.com/evld lynda/evld/merge
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh lynda.com/vail lynda/vail/merge
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh lynda.com/telluride lynda/telluride/merge

#Extracts for sideloaded eContent; settings defined in config.pwd.ini [Sideload]
cd /usr/local/vufind-plus/vufind/cron; ./sideload.sh ${PIKASERVER}

# Merge Learning Express Records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh learning_express/steamboatsprings >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh learning_express/garfield >> ${OUTPUT_FILE}

# Merge Lynda.com Records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh lynda/evld >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh lynda/vail >> ${OUTPUT_FILE}
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh lynda/telluride >> ${OUTPUT_FILE}

# Merge OneClick digital Records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh oneclickdigital/englewood >> ${OUTPUT_FILE}

#Merge EBSCO records
/usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh ebsco/fortlewis >> ${OUTPUT_FILE}

#Extract Lexile Data
cd /data/vufind-plus/; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/lexileTitles.txt https://cassini.marmot.org/lexileTitles.txt

#Extract AR Data
cd /data/vufind-plus/accelerated_reader; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/accelerated_reader/RLI-ARDataTAB.txt https://cassini.marmot.org/RLI-ARDataTAB.txt

#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
DAYOFWEEK=$(date +"%u")
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -server -XX:+UseG1GC -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

FILE=$(find /data/vufind-plus/opac.marmot.org/marc/ -name fullexport.mrc -mtime -1 | sort -n | tail -1)

if [ -n "$FILE" ]
then
  #check file size
	FILE1SIZE=$(wc -c <"$FILE")
	if [ $FILE1SIZE -ge $MINFILE1SIZE ]; then

		echo "Latest export file is " $FILE >> ${OUTPUT_FILE}
		DIFF=$(($FILE1SIZE - $MINFILE1SIZE))
		PERCENTABOVE=$((100 * $DIFF / $MINFILE1SIZE))
		echo "The export file is $PERCENTABOVE (%) larger than the minimum size check." >> ${OUTPUT_FILE}

		#Validate the export
		cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

		#Full Regroup
		cd /usr/local/vufind-plus/vufind/record_grouping; java -server -XX:+UseG1GC -Xmx6G -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

		#TODO: Determine if we should do a partial update from the ILS and OverDrive before running the reindex to grab last minute changes

		#Full Reindex
		cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

	else
		echo $FILE " size " $FILE1SIZE "is less than minimum size :" $MINFILE1SIZE "; Export was not moved to data directory, Full Regrouping & Full Reindexing skipped." >> ${OUTPUT_FILE}
	fi
else
	echo "Did not find a export file from the last 24 hours, Full Regrouping & Full Reindexing skipped." >> ${OUTPUT_FILE}
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

