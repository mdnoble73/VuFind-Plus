#!/bin/bash
while true
do
	echo "Starting new extract and index - `date`"
	#export from sierra
	cd /usr/local/vufind-plus/vufind/sierra_export/; nice -n -10 java -jar sierra_export.jar marmot.training
	#export from overdrive
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/; nice -n -10 java -jar overdrive_extract.jar marmot.training
	#run reindex
	cd /usr/local/vufind-plus/vufind/reindexer; nice -n -5 java -jar reindexer.jar marmot.training
done