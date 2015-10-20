#!/bin/sh
# Copies needed solr files to the server specified as a command line argument
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
fi
rm -f /data/vufind-plus/$1/solr/lib/*
cp -r solr /data/vufind-plus/$1