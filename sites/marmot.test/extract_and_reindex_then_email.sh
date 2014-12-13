#!/bin/bash
# Script executes continuous re-indexing.
#
# this version emails script output as a round finishes
EMAIL=root@venus

OUTPUT_FILE="/var/log/vufind-plus/marmot.test/extract_and_reindex_output.log"
while true
do

	#echo "Starting new extract and index - `date`" > ${OUTPUT_FILE}
	# reset the output file each round
	
	#export from sierra
	#echo "Starting Sierra Export - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/sierra_export/;
	nice -n -10 java -jar sierra_export.jar marmot.test >> ${OUTPUT_FILE}
	
	#export from overdrive
	#echo "Starting OverDrive Extract - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/;
	nice -n -10 java -jar overdrive_extract.jar marmot.test >> ${OUTPUT_FILE}
	
	#run reindex
	#echo "Starting Reindexing - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/reindexer;
	nice -n -5 java -jar reindexer.jar marmot.test >> ${OUTPUT_FILE}

	# add any logic wanted for when to send the emails here. (eg errors only)
	FILESIZE=$(stat -c%s ${OUTPUT_FILE})
	if [$FILESIZE -gt 0]
	then
		# send mail
		mail -s "Extract and Reindexing - marmot.test" $EMAIL < ${OUTPUT_FILE}
	fi
done


