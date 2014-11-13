#!/bin/sh
### BEGIN INIT INFO
# Default-Start: 2 3 4 5
# Default-Stop: 0 1 6
# Description: VuFind init script.  Change {servername} to your server name, 
# move the file to /etc/init.d/ , rename as vufind, make executable,
# and add service to startup options with "chkconfig vufind --levels 2345 on"
### END INIT INFO
cd /usr/local/vufind-plus/sites/{servername}
./{servername}.sh $*