#!/bin/bash

# Move any ftp-ed Marc Export Files to the Data Directory
mv /home/sierraftp/*.MRC /data/vufind-plus/arlington.production/marc
mv /home/sierraftp/*.mrc /data/vufind-plus/arlington.production/marc


# Delete any exports over 7 days
find /data/vufind-plus/arlington.production/marc/ -name *.MRC -type f -mtime +7 -delete
find /data/vufind-plus/arlington.production/marc/ -name *.mrc -type f -mtime +7 -delete