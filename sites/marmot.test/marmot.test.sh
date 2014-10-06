#!/bin/sh
# set local configuration for starting Solr and then start solr
#Replace {servername} with your server name and save in sites/{servername} as {servername.sh} 
export VUFIND_HOME=/usr/local/vufind-plus/sites/marmot.test
export JETTY_HOME=/usr/local/vufind-plus/sites/default/solr/jetty
export SOLR_HOME=/data/vufind-plus/marmot.test/solr
export JETTY_PORT=8080
#Max memory should be at least he size of all solr indexes combined. 
export JAVA_OPTIONS="-server -Xms2g -Xmx22g -XX:+UseParallelGC -XX:NewRatio=5"
export JETTY_LOG=/var/log/vufind-plus/marmot.test/jetty

exec /usr/local/vufind-plus/sites/default/vufind.sh $1 $2
