#!/bin/bash
# Script restores the nightly index that is saved as tar archive file production in the full_update script
#
# Requires .my.cnf settings for mysqldump
	if [ $# = 2 ];then
	PIKASERVER=$1
	PIKADB=$2
	echo "Please ensure continuous and full re-indexing are off"
	read -p "Are you sure you want to restore the back up? " -n 1 -r
	echo    # (optional) move to a new line
	if [[ $REPLY =~ ^[Yy]$ ]]
	then

		# Stop Solr Index
		/usr/local/vufind-plus/${PIKASERVER}/${PIKASERVER}.sh stop

		tar -xzvf /data/vufind-plus/${PIKASERVER}/solr_master_backup.tar.gz -C /
		# TODO success check

		mysql -p ${PIKADB} < /data/vufind-plus/${PIKASERVER}/grouped_work_primary_identifiers.sql

		# Start up solr index
		/usr/local/vufind-plus/${PIKASERVER}/${PIKASERVER}.sh start

	fi

else
  echo ""
  echo "Usage:  $0 {Pika Sites Directory Name for this instance} {main Pika database name}"
  echo "eg: $0 pika.test pika"
  echo ""
  exit 1
fi