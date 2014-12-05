#!/bin/bash
#
# setup_server.sh
#
# author: Steve Lindemann
#   date: 22 Jul 14
#
# setup data and logs for a new vufind-plus server by copying the appropriate 
# files from default
#
#-------------------------------------------------------------------------
# 22 Jul 14 - v0.0.1 - sml - create initial script based on dos batch file
# 05 Dec 14 - v0.1.0 - plb - added permission setting for web user of data & logs
#-------------------------------------------------------------------------
#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------
HOST=$1
WD=`pwd`

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------

if [ $# = 1 ];then
  echo ""
  echo "Working directory is: $WD"
  echo "Server name is: $HOST"
  echo ""
  #-----------------
  echo "setting up data directory"
  cd /data
  mkdir vufind-plus
  cd vufind-plus
  mkdir $HOST
  cd $HOST
  cp -rp $WD/data_dir_setup/* .
  #-----------------
  echo "setting group permissions to data directory for user apache"
  chgrp apache qrcodes
  chgrp apache covers/*
  chmod g+w qrcodes
  chmod g+w covers/*
  #-----------------
  echo "setting up logs directory"
  cd /var/log
  mkdir vufind-plus
  cd vufind-plus
  mkdir $HOST
  cd $HOST
  mkdir jetty
  #-----------------
  echo "setting group permissions to logs for user apache"
  touch error.log messages.log access.log
  chgrp apache error.log messages.log access.log
  chmod g+w error.log messages.log access.log
  #-----------------
  echo ""
  cd $WD
  exit 0
else
  echo ""
  echo "Usage:  $0 <server.domain.tld>"
  echo ""
  exit 1
fi
#
#--eof--
