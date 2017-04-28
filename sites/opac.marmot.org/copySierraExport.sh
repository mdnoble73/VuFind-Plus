#!/bin/sh

#Retrieve marc records from the FTP server
mount 10.1.2.6:/ftp/sierra /mnt/ftp
cp --preserve=timestamps --update /mnt/ftp/fullexport.marc /data/vufind-plus/opac.marmot.org/marc/fullexport.mrc
umount /mnt/ftp

