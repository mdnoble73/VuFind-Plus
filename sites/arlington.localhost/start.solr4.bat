rem Start Solr
rm -f c:\data\vufind-plus\arlington.localhost\solr\lib\*
cp c:/web/VuFind-Plus/data_dir_setup/solr/lib/* c:/data/vufind-plus/arlington.localhost/solr/lib/
..\default\solr\bin\solr.cmd start -p 8086 -m 2g -s "c:\data\vufind-plus\arlington.localhost\solr" -d "c:\web\VuFind-Plus\sites\default\solr\jetty"
