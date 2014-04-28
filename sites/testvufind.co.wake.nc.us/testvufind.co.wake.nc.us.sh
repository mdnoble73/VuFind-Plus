#!/bin/sh
# set local configuration for starting Solr and then start solr
#Replace {servername} with your server name and save in sites/{servername} as {servername.sh} 
export VUFIND_HOME=/usr/local/vufind-plus/sites/testvufind.co.wake.nc.us
export JETTY_HOME=/usr/local/vufind-plus/sites/default/solr/jetty
export SOLR_HOME=/data/vufind-plus/testvufind.co.wake.nc.us/solr
export JETTY_PORT=8080
#Max memory should be at least he size of all solr indexes combined. 
export JAVA_OPTIONS="-server -Xms2g -Xmx8g -XX:+UseParallelGC -XX:NewRatio=5"
export JETTY_LOG=/var/log/vufind-plus/testvufind.co.wake.nc.us/jetty

exec /usr/local/vufind-plus/sites/default/vufind.sh $1 $2
