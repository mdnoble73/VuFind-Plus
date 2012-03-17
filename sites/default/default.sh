#!/bin/sh
# set local configuration for starting Solr and then start solr
export VUFIND_HOME=/usr/local/VuFind-Plus/vufind
export JETTY_HOME=/usr/local/VuFind-Plus/jetty
export SOLR_HOME=/usr/local/VuFind-Plus/solr-data/{libraryname}     
export JETTY_PORT={libraryport}
export JAVA_OPTIONS="-server -Xms1024m -Xmx6144m -XX:+UseParallelGC -XX:NewRatio=5"
export JETTY_LOG=/usr/local/VuFind-Plus/logs/{libraryname}l

exec /usr/local/VuFind-Plus/vufind.sh $1 $2