#!/bin/bash
# Script executes continuous re-indexing.
#
# this version emails script output as a round finishes
EMAIL=root@venus
PIKASERVER=marmot.test
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/extract_and_reindex_output.log"

while true
do
        #truncate the file
        : > $OUTPUT_FILE;

        #echo "Starting new extract and index - `date`" > ${OUTPUT_FILE}
        # reset the output file each round

        #export from sierra
        #echo "Starting Sierra Export - `date`" >> ${OUTPUT_FILE}
        cd /usr/local/vufind-plus/vufind/sierra_export/
        nice -n -10 java -server -XX:+UseG1GC -jar sierra_export.jar ${PIKASERVER} >> ${OUTPUT_FILE}

        #export from overdrive
        #echo "Starting OverDrive Extract - `date`" >> ${OUTPUT_FILE}
        cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
        nice -n -10 java -server -XX:+UseG1GC -jar overdrive_extract.jar ${PIKASERVER} >> ${OUTPUT_FILE}

        #run reindex
        #echo "Starting Reindexing - `date`" >> ${OUTPUT_FILE}
        cd /usr/local/vufind-plus/vufind/reindexer
        nice -n -5 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} >> ${OUTPUT_FILE}

        # add any logic wanted for when to send the emails here. (eg errors only)
        FILESIZE=$(stat -c%s ${OUTPUT_FILE})
        if [[ ${FILESIZE} > 0 ]]
        then
                # send mail
                mail -s "Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
        fi
done
