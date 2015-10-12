rem Start Solr
rem @set SOLR_HOME=c:\data\vufind-plus\marmot.localhost\solr
rem @set JETTY_HOME=c:\web\VuFind-Plus\sites\default\solr\jetty

..\default\solr5\bin\solr.cmd start -p 8080 -s "c:\data\vufind-plus\marmot.localhost\solr" -d "c:\web\VuFind-Plus\sites\default\solr5\server"
