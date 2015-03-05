#!/bin/sh
# set local configuration for starting Solr and then start solr
#Replace {servername} with your server name and save in sites/{servername} as {servername.sh}
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

##################################################
# Setup the call to start solr
##################################################
export VUFIND_HOME=/usr/local/vufind-plus/sites/marmot.training
export JETTY_HOME=/usr/local/vufind-plus/sites/default/solr/jetty
export SOLR_HOME=/data/vufind-plus/marmot.training/solr
export JETTY_PORT=8082
JETTY_RUN=`findDirectory -w /var/run /usr/var/run /tmp`
export JETTY_RUN
export JETTY_PID=$JETTY_RUN/marmot.training.pid

#Max memory should be at least the size of all solr indexes combined.
export JAVA_OPTIONS="-server -Xms2g -Xmx22g -XX:+UseParallelGC -XX:NewRatio=5"
export JETTY_LOG=/var/log/vufind-plus/marmot.training/jetty

exec /usr/local/vufind-plus/sites/default/vufind.sh $1 $2

