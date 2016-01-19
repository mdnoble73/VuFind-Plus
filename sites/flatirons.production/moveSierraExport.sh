#!/bin/bash

# Date For Backup filename
TODAY=$(date +"%m_%d_%Y")

FILE=$(find /home/sierraftp/ -name FULLEXPORT1*.MRC | sort -n | tail -1)
echo "Latest export file is " $FILE
# turn off output. TODO: error check that a good file name was found

# Copy to data directory to process
cp $FILE /data/vufind-plus/flatirons.production/marc/pika1.mrc
# Move to marc_export to keep as a backup
mv $FILE /data/vufind-plus/flatirons.production/marc_export/pika.$TODAY.mrc

# Delete any exports over 7 days
find /data/vufind-plus/flatirons.production/marc_export/ -mindepth 1 -maxdepth 1 -name *.mrc -type f -mtime +7 -delete
