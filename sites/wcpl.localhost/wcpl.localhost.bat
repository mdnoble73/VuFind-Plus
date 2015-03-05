@set VUFIND_HOME=c:\web\VuFind-Plus\sites\wcpl.localhost
@set JETTY_HOME=c:\web\VuFind-Plus\sites\default\solr\jetty
@set SOLR_HOME=c:\data\vufind-plus\wcpl.localhost\solr
@set JETTY_PORT=8084
@set JETTY_LOG=c:\var\logs\vufind-plus\wcpl.localhost\logs\jetty
@call ..\default\run_vufind.bat start %1 %2 %3 %4 %5 %6 %7 %8 %9
