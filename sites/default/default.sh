#!/bin/sh
# set local configuration for starting Solr and then start solr

SERVERNAME={servername}
#Replace {servername} with your server name and save in sites/{servername} as {servername}.sh

# needed for cases where multiple solr engines are running at the same time
# (eg. for several vufind instances)
##################################################
# Find directory function
##################################################
findDirectory()
{
OP=$1
shift
for L in $* ; do
[ $OP $L ] || continue
echo $L
break
done
}

#################################################
# Setup the call to start solr
################################################## 
export VUFIND_HOME=/usr/local/VuFind-Plus/sites/$SERVERNAME
export JETTY_HOME=/usr/local/VuFind-Plus/sites/$SERVERNAME/solr/jetty
export SOLR_HOME=/usr/local/VuFind-Plus/sites/$SERVERNAME/solr     
export JETTY_PORT=8080

# check the right instances
JETTY_RUN=`findDirectory -w /var/run /usr/var/run /tmp`
export JETTY_RUN
export JETTY_PID=$JETTY_RUN/$SERVERNAME.pid

#Max memory should be at least the size of all solr indexes combined. 
export JAVA_OPTIONS="-server -Xms1024m -Xmx6144m -XX:+UseG1GC"
#export JETTY_LOG=/usr/local/VuFind-Plus/sites/$SERVERNAME/logs/jetty
export JETTY_LOG=/var/log/VuFind-Plus/$SERVERNAME/logs/jetty
# this is the usual directory for log files. plb 12-10-2014

exec /usr/local/VuFind-Plus/sites/default/vufind.sh $1 $2