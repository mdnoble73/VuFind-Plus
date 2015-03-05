@set VUFIND_HOME=c:\web\VuFind-Plus\sites\marmot.localhost
@set JETTY_HOME=c:\web\VuFind-Plus\sites\default\solr\jetty
@set SOLR_HOME=c:\data\vufind-plus\marmot.localhost\solr
@set JETTY_PORT=8080
@set JETTY_LOG=C:\var\log\vufind-plus\marmot.localhost\jetty
@call ..\default\run_vufind.bat start %1 %2 %3 %4 %5 %6 %7 %8 %9
