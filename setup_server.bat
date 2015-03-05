@echo off
rem Setup data and logs for a new server by copying the appropriate files from default. 
set SERVERNAME=%1
shift

if not "!%SERVERNAME%!"=="!!" goto serverset
goto usage
:serverset

rem Do the bulk of the copy work

rem get the directory we started with 
set WORKINGDIR=%CD%
echo WORKINGDIR is %WORKINGDIR%

echo setting up data directory
cd c:\data
mkdir vufind-plus
cd c:\data\vufind-plus
mkdir %SERVERNAME%
cd %WORKINGDIR%
cp -rp data_dir_setup/* c:\data\vufind-plus\%SERVERNAME%

echo setting up logs directory
cd c:\var\log
mkdir vufind-plus
cd c:\var\log\vufind-plus
mkdir %SERVERNAME%
cd c:\var\log\vufind-plus\%SERVERNAME%
mkdir jetty
cd %WORKINGDIR%
goto end

:usage
echo Usage: setup_server {servername}
echo.
goto end

:end
rem We're all done -- close down the local environment.
endlocal