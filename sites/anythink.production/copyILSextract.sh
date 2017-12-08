#!/bin/sh

#Retrieve marc records from the FTP server
mount 10.1.2.7:/ftp/anythink /mnt/ftp
# sftp.marmot.org server

cp --preserve=timestamps --update /mnt/ftp/RLDexport.mrc /data/vufind-plus/anythink.production/marc/fullexport.mrc
umount /mnt/ftp

