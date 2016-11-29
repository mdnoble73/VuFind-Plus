#!/usr/bin/env bash
#Retrieve marc records from the FTP server
mount 10.1.2.6:/ftp/sierra /mnt/ftp
#copy production extract
cp /mnt/ftp/fullexport.marc /data/vufind-plus/marmot.test/marc/fullexport.mrc
umount /mnt/ftp

