#!/bin/sh
rm -f /data/vufind-plus/flatirons.test/solr/lib/*
../default/solr5/bin/solr start -m 6g -p 8085 -s "/data/vufind-plus/flatirons.test/solr" -d "/usr/local/vufind-plus/sites/default/solr5/server"