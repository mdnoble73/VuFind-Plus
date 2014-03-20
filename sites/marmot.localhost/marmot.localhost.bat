@set VUFIND_HOME=c:\web\VuFind-Plus\sites\marmot.localhost
@set JETTY_HOME=c:\web\VuFind-Plus\sites\marmot.localhost\solr\jetty
@set SOLR_HOME=c:\web\VuFind-Plus\sites\marmot.localhost\solr    
@set JETTY_PORT=8080
@set JETTY_LOG=c:\web\VuFind-Plus\sites\marmot.localhost\logs\jetty
@call ..\default\run_vufind.bat start %1 %2 %3 %4 %5 %6 %7 %8 %9
