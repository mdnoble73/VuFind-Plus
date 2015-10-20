@echo off
if "%1"=="" goto usage

rm -f c:\data\vufind-plus\%1\solr\lib\*
cp -r solr c:/data/vufind-plus/%1

goto done

:usage
echo You must provide the name of the server to setup

:done