#!/bin/bash
#
# author Pascal Brammeier
#
#-------------------------------------------------------------------------
#  SideLoad Data Directory Set up Script
#-------------------------------------------------------------------------
COLLECTION = $1
LIBRARY = $2
LOCATION = $3

DIR = /data/vufind-plus

if [ $# = 2 ];then
  echo ""
  echo "The Side Load Collection is: $COLLECTION"
  echo "The Library is: $LIBRARY"
  echo ""

  #Check that Collection Dir Exists; if not, create
  DIR = "$DIR/$COLLECTION"
  if [ ! -d "$DIR" ]; then
    echo "Creating $DIR"
    mkdir "$DIR"
  fi

  #Check that Library Dir Exists, if does exit with error; if not create dir
	DIR = "$DIR/$LIBRARY"
  if [ ! -d "$DIR" ]; then
    echo "Creating $DIR"
    mkdir "$DIR"
  fi

	#copy sideload data dir structure to path
  cp -r /usr/local/vufind-plus/data_dir_setup/sideload_data_dir_template/* $DIR

  #edit the merge configuration file
  cat $DIR/mergeConfig.ini|sed -r "s/SIDELOADCOLLECTION/$COLLECTION/'|sed -r 's/LIBRARY/$LIBRARY/"

else
  echo ""
  echo "Usage:  $0 {SideLoadCollection}  {Library}"
  echo "eg: $0 learning_express evld"
  echo ""
  exit 1
fi
#
#--eof--
