#!/bin/bash
while true
do
	echo "Starting new extract and index - `date`"
	#export from sierra
	cd /usr/local/vufind-plus/vufind/sierra_export/; nice -n -10 java -jar sierra_export.jar opac.marmot.org
	#export from overdrive
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/; nice -n -10 java -jar overdrive_extract.jar opac.marmot.org
	#run reindex
	cd /usr/local/vufind-plus/vufind/reindexer; nice -n -5 java -jar reindexer.jar opac.marmot.org
done