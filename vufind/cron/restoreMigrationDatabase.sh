#!/usr/bin/env bash
if [[ $# -eq 4 ]]; then
	echo "You must provide 4 parameters: username, password, Pika database name, name of the file to be restored"
fi
USER=$1
PASSWORD=$2
DBNAME=$3
BACKUPFILE=$4
mysql --user=$USER --password=$PASSWORD $DBNAME < $BACKUPFILE
#TODO - truncate tables?
#truncate table cron_log
#truncate table cron_process_log