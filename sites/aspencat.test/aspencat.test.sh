#!/bin/sh

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [[ ( "$1" == "stop" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr stop -p 8081 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi

if [[ ( "$1" == "start" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr start -m 4g -p 8081 -s "/data/vufind-plus/aspencat.test/solr" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi