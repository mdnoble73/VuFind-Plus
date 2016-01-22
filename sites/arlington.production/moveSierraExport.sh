#!/bin/bash

# Date For Backup filename
TODAY=$(date +"%m_%d_%Y")

FILE1=$(find /home/sierraftp/ -name FULLEXPORT1*.MRC | sort -n | tail -1)
FILE2=$(find /home/sierraftp/ -name FULLEXPORT2*.MRC | sort -n | tail -1)
#echo "Latest file (1) is " $FILE
#echo "Latest file (2) is " $FILE
# turn off output. TODO: error check that a good file name was found

MINFILE1SIZE=$((735000000))
MINFILE2SIZE=$((45000000))
FILE1SIZE=$(wc -c <"$FILE1")
if [ $FILE1SIZE -ge $MINFILE1SIZE ]; then
 FILE2SIZE=$(wc -c <"$FILE2")
 if [ $FILE2SIZE -ge $MINFILE2SIZE ]; then

	# Copy to data directory to process
	cp $FILE1 /data/vufind-plus/arlington.production/marc/pika1.mrc
	# Move to marc_export to keep as a backup
	mv $FILE1 /data/vufind-plus/arlington.production/marc_export/pika1.$TODAY.mrc

	# Copy to data directory to process
	cp $FILE2 /data/vufind-plus/arlington.production/marc/pika2.mrc
	# Move to marc_export to keep as a backup
	mv $FILE2 /data/vufind-plus/arlington.production/marc_export/pika2.$TODAY.mrc

	# Delete any exports over 7 days
	find /data/vufind-plus/arlington.production/marc_export/ -mindepth 1 -maxdepth 1 -name *.mrc -type f -mtime +7 -delete

	else
		echo $FILE2 " size " $FILE2SIZE "is less than minimum size :" $MINFILE2SIZE "; Export was not moved to data directory."
	fi
else
	echo $FILE1 " size " $FILE1SIZE "is less than minimum size :" $MINFILE1SIZE "; Export was not moved to data directory."
fi