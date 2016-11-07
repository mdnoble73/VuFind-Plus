#!/bin/bash
#
# copyCovers.sh
#
# author: Mark Noble
#   date:
#
# script is run by cron every 10 minutes.
#
#-------------------------------------------------------------------------
# 19 Mar 14 - v0.0.2 - sml - updated for maintainablility
# 19 Mar 14 - v0.0.3 - sml - modified to copy files chgd in the last 90 min
# 31 Mar 14 - v0.0.4 - sml - add logging
# 01 Apr 14 - v0.0.5 - sml - add bail out if mount fails, change time to 61m
# 24 Apr 14 - v0.0.6 - mdn - update to use new data directory
#                            add optional all parameter to force copy of
#                            all covers
# 15 Sep 14 - v0.0.7 - plb - re-adjusted to 10 minute intervals
# 14 Nov 14          - plb - new copy for AspenCat.Test created
#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------
REMOTE="10.1.2.6:/ftp"
LOCAL="/mnt/ftp"
SRC="/mnt/ftp/aspencat_covers"
DEST="/data/vufind-plus/aspencat.test/covers/original"
LOG="logger -t copyCovers "

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------
$LOG "~> starting copyCovers-AspenCatTest.sh"

#------------------------------------------------
# mount external drive
#------------------------------------------------
$LOG "~> mount $REMOTE $LOCAL"
mount $REMOTE $LOCAL
EXITCODE=$?
$LOG "~> exit code $EXITCODE"
if [ $EXITCODE -ne 0 ];then
  $LOG "!! script terminated abnormally"
  exit 1
fi

#------------------------------------------------
# copy new files from SRC to DEST
#------------------------------------------------
# set lookup interval to 11 minutes. pascal 9-15-14

if [ -z "$1" ]
then
  $LOG "~> find $SRC -type f -mmin -21 -exec /bin/cp {} $DEST \;"
  find $SRC -type f -mmin -21 -exec /bin/cp {} $DEST \;
  $LOG "~> exit code $?"
else
  /bin/cp $SRC/* $DEST
fi

#------------------------------------------------
# fix ownership/perms on all files in DEST
#------------------------------------------------
cd $DEST
$LOG "~> fix ownership"
chown -R root:apache *
$LOG "~> exit code $?"
$LOG "~> fix permissions"
chmod 660 *
$LOG "~> exit code $?"

#------------------------------------------------
# umount the external drive
#------------------------------------------------
$LOG "~> unmount $LOCAL"
umount $LOCAL
EXITCODE=$?
$LOG "~> exit code $EXITCODE"
if [ $EXITCODE -ne 0 ];then
  $LOG "!! script terminated abnormally"
  $LOG "!! $LOCAL needs UNMOUNTED BEFORE the next script execution"
  exit 3
fi

$LOG "~> finished copyCovers-AspencatTest.sh"
#-------------------------------------------------------------------------
#-- eof --
