#!/bin/sh
# set local configuration for starting Solr and then start solr
#Replace {servername} with your server name and save in sites/{servername} as {servername.sh}

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
export VUFIND_HOME=/usr/local/VuFind-Plus/sites/{servername}
export JETTY_HOME=/usr/local/VuFind-Plus/sites/{servername}/solr/jetty
export SOLR_HOME=/usr/local/VuFind-Plus/sites/{servername}/solr     
export JETTY_PORT=8080

# check the right instances
JETTY_RUN=`findDirectory -w /var/run /usr/var/run /tmp`
export JETTY_RUN
export JETTY_PID=$JETTY_RUN/{servername}.pid

#Max memory should be at least the size of all solr indexes combined. 
export JAVA_OPTIONS="-server -Xms1024m -Xmx6144m -XX:+UseParallelGC -XX:NewRatio=5"
export JETTY_LOG=/usr/local/VuFind-Plus/sites/{servername}/logs/jetty

exec /usr/local/VuFind-Plus/sites/default/vufind.sh $1 $2