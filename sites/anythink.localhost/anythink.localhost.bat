@echo off
if "%1"=="start" goto start
if "%1"=="stop" goto stop
if "%1"=="restart" goto stop
goto usage

:start
rem Start Solr
call ..\default\solr\bin\solr.cmd start -p 8082 -m 2g -s "c:\data\vufind-plus\anythink.localhost\solr" -d "c:\web\VuFind-Plus\sites\default\solr\jetty"
goto done

:stop
rem Stop Solr
call ..\default\solr5\bin\solr.cmd stop -p 8082 -d "c:\web\VuFind-Plus\sites\default\solr\jetty"
if "%1"=="restart" goto start
goto done

:usage
echo Please provide a parameter start or stop to start/stop solr or restart to stop solr and then start it again

:done
