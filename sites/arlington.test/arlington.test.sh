#!/bin/sh

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [[ ( "$1" == "stop" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr stop -p 8186 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
		../default/solr/bin/solr stop -p 8086 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi

if [[ ( "$1" == "start" ) || ( "$1" == "restart") ]]
	then
	../default/solr/bin/solr start -m 2g -p 8186 -s "/data/vufind-plus/arlington.test/solr_master" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
	../default/solr/bin/solr start -m 2g -p 8086 -a "-Dsolr.masterport=8186" -s "/data/vufind-plus/arlington.test/solr_searcher" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi