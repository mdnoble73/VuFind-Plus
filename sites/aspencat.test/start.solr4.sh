#!/bin/sh
rm -f /data/vufind-plus/aspencat.test/solr/lib/*
cp /usr/local/vufind-plus/data_dir_setup/solr/lib/*.* /data/vufind-plus/aspencat.test/solr/lib/

../default/solr/bin/solr start -m 6g -p 8081 -s "/data/vufind-plus/aspencat.test/solr" -d "/usr/local/vufind-plus/sites/default/solr/jetty"