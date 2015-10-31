#!/bin/bash

# full_update.sh
# Mark Noble, Marmot Library Network
# James Staub, Nashville Public Library
# Script handles all aspects of a full index including 
# extracting data from other systems.
# Should be called once per day from crontab
# For Pika discovery partners using Millennium 2014 2.0.0_15

# TO DO: 
#	+ add similar isProduction logic to continuous_partial_reindex.sh

# 201509xx : changes below for moving to production
# 	+ until pika is in production, galacto is considered production
#		and pika is considered test
#	+ when pika moves to production: 
#		+ change config.pwd.ini [Site][isProduction]
#			+ of galacto to false
#			+ of catalog to true
#		+ alter scp statements to refer to catalog, not galacto
#		+ ensure SSH keys are set up appropriately

# 20150818 : changes in preparation for pika moving from dev to test
#	+ check isProduction value from config.ini
#	+ eliminate checkProhibitedTimes; Pika uses a different set of 
#		Review Files than VF+ and the non-production pika
#		machine should simply scp files from production server

# 20150219 : version 1.0


# this version emails script output as a round finishes
EMAIL=james.staub@nashville.gov,Mark.Noble@nashville.gov,Pascal.Brammeier@nashville.gov
ILSSERVER=waldo.library.nashville.org
PIKASERVER=catalog.library.nashville.org
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"
DAYOFWEEK=$(date +"%u")

# determine whether this server is production or test
CONFIG=/usr/local/VuFind-Plus/sites/${PIKASERVER}/conf/config.pwd.ini
#echo ${CONFIG}
if [ ! -f ${CONFIG} ]; then
        CONFIG=/usr/local/vufind-plus/sites/${PIKASERVER}/conf/config.pwd.ini
        #echo ${CONFIG}
        if [ ! -f ${CONFIG} ]; then
                echo "Please check spelling of site ${PIKASERVER}; conf.pwd.ini not found at $confpwd"
                exit
        fi
fi
function trim()
{
    local var=$1;
    var="${var#"${var%%[![:space:]]*}"}";   # remove leading whitespace characters
    var="${var%"${var##*[![:space:]]}"}";   # remove trailing whitespace characters
    echo -n "$var";
}
while read line; do
        if [[ $line =~ ^isProduction ]]; then
                PRODUCTION=$(trim "${line#*=}");
        fi
done < "${CONFIG}"

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
#Since we aren't running in a loop, check in the order they run.
checkConflictingProcesses "ITEM_UPDATE_EXTRACT_PIKA.exp"
checkConflictingProcesses "millennium_export.jar"
checkConflictingProcesses "overdrive_extract.jar"
checkConflictingProcesses "reindexer.jar"

# truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
if [ ${PRODUCTION} == "false" ]; then
# if development or test, scp from production as vufind@catalog.library.nashville.org
# requires setting up SSH keys for vufind:
#	on dev, log in as vufind
#	$ ssh-keygen
#	[accept defaults]
#	$ cat .ssh/id_rsa.pub
#	copy public key
#	on production, log in as vufind
#	$ nano .ssh/authorized_keys
#	paste public key
#	save
#	$ chmod 700 .ssh
#	$ chmod 640 authorized_keys
#
	cd /data/vufind-plus/catalog.library.nashville.org/marc/; 
	cp BIB_EXTRACT_PIKA.MRC BIB_EXTRACT_PIKA.SAV
	scp -p -i /home/vufind/.ssh/id_rsa vufind@catalog.library.nashville.org:/data/vufind-plus/catalog.library.nashville.org/marc/BIB_EXTRACT_PIKA.MRC ./;
	chown vufind:vufind BIB_EXTRACT_PIKA.MRC
	chmod 664 BIB_EXTRACT_PIKA.MRC
	scp -p -i /home/vufind/.ssh/id_rsa vufind@catalog.library.nashville.org:/data/vufind-plus/catalog.library.nashville.org/marc/BIB_HOLDS_EXTRACT_PIKA.TXT ./;
	chown vufind:vufind BIB_HOLDS_EXTRACT_PIKA.TXT
	chmod 664 BIB_HOLDS_EXTRACT_PIKA.TXT
elif [ ${PRODUCTION} == "true" ]; then
# FOR PRODUCTION
	cd /usr/local/VuFind-Plus/vufind/millennium_export/; expect -f BIB_HOLDS_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER} >> ${OUTPUT_FILE}
	cd /usr/local/VuFind-Plus/vufind/millennium_export/; expect -f BIB_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER} >> ${OUTPUT_FILE}
fi

#Extract from Hoopla
cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}

#Extract Lexile Data
cd /data/vufind-plus/; 
#rm lexileTitles.txt*; 
wget -N -q http://venus.marmot.org/lexileTitles.txt

#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

# should test for new bib extract file
# should copy old bib extract file

#Validate the export
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; 
java -server -XX:+UseG1GC -Xmx6G -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#Full Reindex
#cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}
cd /usr/local/vufind-plus/vufind/reindexer; 
java -server -XX:+UseG1GC -Xmx6G -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

#Remove all ITEM_UPDATE_EXTRACT_PIKA files so continuous_partial_reindex can start fresh
find /data/vufind-plus/catalog.library.nashville.org/marc -name 'ITEM_UPDATE_EXTRACT_PIKA*' -delete

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
	# send mail
	mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi
