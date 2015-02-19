#!/bin/bash
# Mark Noble
# on behalf of Nashville Public Library
# 20150218
# Script executes continuous re-indexing.
#

# this version emails script output as a round finishes
EMAIL=James.Staub@nashville.gov,mark@marmot.org,pascal@marmot.org
PIKASERVER=catalog.library.nashville.org
ILSSERVER=waldo.library.nashville.org
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/continuous_partial_reindex_output.log"

# Prohibited time ranges - for, e.g., ILS backup
# JAMES is currently giving all Nashville prohibited times a ten minute buffer

# Nashville Millennium backup
backupStart=22:55:00
backupStop=02:30:00

# Nashville Full Record Group/Reindex
fullReindexStart=22:35:00
fullReindexStop=05:30:00


while true 
do

	NOW=$(date +%H:%M:%S)
	NOW=$(date --date=$NOW +%s)

	backupStart=$(date --date=$backupStart +%s)
	backupStop=$(date --date=$backupStop +%s)

	if (( $backupStart > $backupStop ))
	then
		overMidnight=true
		backupStop=$backupStop+86400
	fi

	if (( $NOW > $backupStart && $NOW < $backupStop ))
	then
		sleep $(($backupStop - $NOW))
	fi

	fullReindexStart=$(date --date=$fullReindexStart +%s)
	fullReindexStop=$(date --date=$fullReindexStop +%s)
	if (( $fullReindexStart > $fullReindexStop ))
	then
		fullReindexStop=$fullReindexStop+86400
	fi
	if (( $NOW > $fullReindexStart && $NOW < $fullReindexStop ))
	then
		sleep $(($fullReindexStop - $NOW))
	fi

        #truncate the file
        : > $OUTPUT_FILE;

        #echo "Starting new extract and index - `date`" > ${OUTPUT_FILE}
        # reset the output file each round

        #run expect script to extract from Millennium
        #do not log to the output file since it has lots of data in it.
        cd /usr/local/vufind-plus/vufind/millennium_export/
        ./ITEM_UPDATE_EXTRACT_PIKA.exp ${PIKASERVER} ${ILSSERVER}

        #process the export from Millennium to give Pika what it needs
        #echo "Starting Millennium Export - `date`" >> ${OUTPUT_FILE}
        cd /usr/local/vufind-plus/vufind/millennium_export/
        nice -n -10 java -jar millennium_export.jar ${PIKASERVER} >> ${OUTPUT_FILE}

        #export from overdrive
        #echo "Starting OverDrive Extract - `date`" >> ${OUTPUT_FILE}
        cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
        nice -n -10 java -jar overdrive_extract.jar ${PIKASERVER} >> ${OUTPUT_FILE}

        #run reindex
        #echo "Starting Reindexing - `date`" >> ${OUTPUT_FILE}
        cd /usr/local/vufind-plus/vufind/reindexer
        nice -n -5 java -jar reindexer.jar ${PIKASERVER} >> ${OUTPUT_FILE}

        # add any logic wanted for when to send the emails here. (eg errors only)
        FILESIZE=$(stat -c%s ${OUTPUT_FILE})
        if [[ ${FILESIZE} > 0 ]]
        then
                # send mail
                mail -s "Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
        fi
done
