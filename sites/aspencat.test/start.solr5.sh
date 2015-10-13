#!/bin/sh
rm -f /data/vufind-plus/aspencat.test/solr/lib/*
../default/solr5/bin/solr start -m 6g -p 8081 -s "/data/vufind-plus/aspencat.test/solr" -d "/usr/local/vufind-plus/sites/default/solr5/server"