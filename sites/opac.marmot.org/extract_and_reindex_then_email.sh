#!/bin/bash
# Script executes continuous re-indexing.
#
# this version emails script output as a round finishes
EMAIL=root@mercury

OUTPUT_FILE="/var/log/vufind-plus/opac.marmot.org/extract_and_reindex_output.log"
while true
do
	#truncate the file
	: > $OUTPUT_FILE;

	#echo "Starting new extract and index - `date`" > ${OUTPUT_FILE}
	# reset the output file each round
	
	#export from sierra
	#echo "Starting Sierra Export - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/sierra_export/;
	nice -n -10 java -jar sierra_export.jar opac.marmot.org >> ${OUTPUT_FILE}
	
	#export from overdrive
	#echo "Starting OverDrive Extract - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/;
	nice -n -10 java -jar overdrive_extract.jar opac.marmot.org >> ${OUTPUT_FILE}
	
	#run reindex
	#echo "Starting Reindexing - `date`" >> ${OUTPUT_FILE}
	cd /usr/local/vufind-plus/vufind/reindexer;
	nice -n -5 java -jar reindexer.jar opac.marmot.org >> ${OUTPUT_FILE}

	# add any logic wanted for when to send the emails here. (eg errors only)
	FILESIZE=$(stat -c%s ${OUTPUT_FILE})
	if [[ ${FILESIZE} > 0 ]]
	then
		# send mail
		mail -s "Extract and Reindexing - mercury.marmot.org" $EMAIL < ${OUTPUT_FILE}
	fi
done


