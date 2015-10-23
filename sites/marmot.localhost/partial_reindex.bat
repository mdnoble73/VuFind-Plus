@echo off
#export from sierra (items, holds, and orders)
cd c:/web/vufind-plus/vufind/sierra_export/
java -server -jar sierra_export.jar marmot.localhost

#export from overdrive
#echo "Starting OverDrive Extract - `date`"
cd c:/web/vufind-plus/vufind/overdrive_api_extract/
java -server -jar overdrive_extract.jar marmot.localhost

#run reindex
#echo "Starting Reindexing - `date`"
cd c:/web/vufind-plus/vufind/reindexer
java -server -jar reindexer.jar marmot.localhost

cd c:/web/vufind-plus/sites/marmot.localhost