rem Start Solr
rem @set SOLR_HOME=c:\data\vufind-plus\marmot.localhost\solr
rem @set JETTY_HOME=c:\web\VuFind-Plus\sites\default\solr\jetty
rm -f c:\data\vufind-plus\marmot.localhost\solr\lib\*

..\default\solr5\bin\solr.cmd start -p 8080 -m 2g -s "c:\data\vufind-plus\marmot.localhost\solr" -d "c:\web\VuFind-Plus\sites\default\solr5\server"
