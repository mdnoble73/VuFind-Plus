#!/bin/sh

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [ "$1" eq "stop" or "$1" eq "restart" ]
	then
		../default/solr/bin/solr stop -p 8085 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi

if [ "$1" eq "start" or "$1" eq "restart" ]
	then
		../default/solr/bin/solr start -m 4g -p 8085 -s "/data/vufind-plus/flatirons.test/solr" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi