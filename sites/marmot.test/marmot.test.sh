#!/bin/sh
# set local configuration for starting Solr and then start solr
#Replace {servername} with your server name and save in sites/{servername} as {servername.sh} 

# needed for cases where multiple solr engines are running at the same time
# (eg. for several Pika instances)
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
export VUFIND_HOME=/usr/local/vufind-plus/sites/marmot.test
export JETTY_HOME=/usr/local/vufind-plus/sites/default/solr/jetty

#pika instance of solr engine
export SOLR_HOME=/data/vufind-plus/marmot.test/solr
export JETTY_PORT=8080

# check the right instances
JETTY_RUN=`findDirectory -w /var/run /usr/var/run /tmp`
export JETTY_RUN
export JETTY_PID=$JETTY_RUN/marmot.test.pid

#Max memory should be at least the size of all solr indexes combined.
export JAVA_OPTIONS="-server -Xms2g -Xmx22g -XX:+UseG1GC"
export JETTY_LOG=/var/log/vufind-plus/marmot.test/jetty

exec /usr/local/vufind-plus/sites/default/vufind.sh $1 $2
