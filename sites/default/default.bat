@set VUFIND_HOME=d:\web\tltvufind\vufind
@set JETTY_HOME=d:\web\tltvufind\vufind\solr\jetty
@set SOLR_HOME=d:\web\tltvufind\solr-data\{libraryname}     
@set JETTY_PORT={libraryport}
@set JETTY_LOG=d:\web\tltvufind\logs\{libraryname}\jetty
@call run_vufind.bat start %1 %2 %3 %4 %5 %6 %7 %8 %9
