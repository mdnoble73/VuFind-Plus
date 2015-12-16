#!/bin/bash

# Date For Backup filename
TODAY=$(date +"%m_%d_%Y")

FILE=$(find /home/sierraftp/ -name FULLEXPORT1*.MRC | sort -n | tail -1)
echo "Latest file (1) is " $FILE
# Copy to data directory to process
cp $FILE /data/vufind-plus/arlington.production/marc/pika1.mrc
# Move to marc_export to keep as a backup
mv $FILE /data/vufind-plus/arlington.production/marc_export/pika1.$TODAY.mrc

FILE=$(find /home/sierraftp/ -name FULLEXPORT2*.mrc | sort -n | tail -1)
echo "Latest file (2) is " $FILE
# Copy to data directory to process
cp $FILE /data/vufind-plus/arlington.production/marc/pika2.mrc
# Move to marc_export to keep as a backup
mv $FILE /data/vufind-plus/arlington.production/marc_export/pika2.$TODAY.mrc

# Delete any exports over 7 days
find /data/vufind-plus/arlington.production/marc_export/ -name *.mrc -type f -mindepth 1 -maxdepth 1 -mtime +7 -delete
