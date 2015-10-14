#!/bin/sh
rm -f /data/vufind-plus/arlington.test/solr/lib/*
cp /usr/local/vufind-plus/data_dir_setup/solr/lib/*.* /data/vufind-plus/arlington.test/solr/lib/

../default/solr/bin/solr start -m 4g -p 8086 -s "/data/vufind-plus/arlington.test/solr" -d "/usr/local/vufind-plus/sites/default/solr/jetty"