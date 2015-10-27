#!/bin/bash
# Script executes continuous re-indexing.
while true
do
	echo "Starting new extract and index - `date`"
	#export from sierra
	cd /usr/local/vufind-plus/vufind/sierra_export/; nice -n -10 java -server -XX:+UseG1GC -jar sierra_export.jar flatirons.test
	#export from overdrive
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/; nice -n -10 java -server -XX:+UseG1GC -jar overdrive_extract.jar flatirons.test
	#run reindex
	cd /usr/local/vufind-plus/vufind/reindexer; nice -n -5 java -server -XX:+UseG1GC -jar reindexer.jar flatirons.test
done