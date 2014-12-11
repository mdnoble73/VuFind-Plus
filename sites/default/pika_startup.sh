#!/bin/sh
### BEGIN INIT INFO
# Provides: pika
# Required-Start: mysql httpd memcached
# Default-Start: 2 3 4 5
# Default-Stop: 0 1 6
# Description: Pika init script. (formerly known as VuFind)  Change {servername} to your server name,
# move the file to /etc/init.d/ , rename as pika, make executable,
# and add service to startup options with "chkconfig pika on"
### END INIT INFO

# Solr Engine for Marmot Test instance
cd /usr/local/vufind-plus/sites/{servername}
./{servername}.sh start
