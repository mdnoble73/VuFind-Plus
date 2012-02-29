@echo off
rem #####################################################
rem Make sure that environment edits are local and that we have access to the 
rem Windows command extensions.
rem #####################################################
setlocal enableextensions
if not errorlevel 1 goto extensionsokay
echo Unable to enable Windows command extensions.
goto end
:extensionsokay
set LIBRARY=%1
shift

rem ##################################################
rem # Set SOLR_HOME
rem ##################################################
set SOLR_HOME=..\..\solr-data\%LIBRARY%
:solrhomefound

rem #####################################################
rem # Build java command
rem #####################################################
if not "!%JAVA_HOME%!"=="!!" goto javahomefound
set JAVA=java
goto javaset
:javahomefound
set JAVA="%JAVA_HOME%\bin\java"
:javaset

SET bib_index=..\..\solr-data\%LIBRARY%\biblio\index
SET auth_index=..\..\solr-data\%LIBRARY%\authority\index
SET index_dir=..\..\solr-data\%LIBRARY%\alphabetical_browse

rem #####################################################
rem If we're being called for the build_browse function, jump there now:
rem #####################################################
if "!%1!"=="!build_browse!" goto build_browse

rem #####################################################
rem If we got this far, we want to go through the main logic:
rem #####################################################
if exist %index_dir% goto nomakeindexdir
mkdir "%index_dir%"
:nomakeindexdir

echo "Building indexes for %LIBRARY%"
call .\index-alphabetic-browse.bat %LIBRARY% build_browse title title_fullStr 1 "-Dbibleech=StoredFieldLeech -Dsortfield=title_sort -Dvaluefield=title_fullStr"
call .\index-alphabetic-browse.bat %LIBRARY% build_browse topic topic_browse
call .\index-alphabetic-browse.bat %LIBRARY% build_browse author author_browse
call .\index-alphabetic-browse.bat %LIBRARY% build_browse lcc callnumber-a 1
call .\index-alphabetic-browse.bat %LIBRARY% build_browse dewey dewey-raw 1 "-Dbibleech=StoredFieldLeech -Dsortfield=dewey-sort -Dvaluefield=dewey-raw"
call .\index-alphabetic-browse.bat %LIBRARY% build_browse callnumber callnumber_brows
goto end

rem Function to process a single browse index:
:build_browse
shift
SET browse=%1
SET field=%2
SET jvmopts=%4

rem Strip double quotes from JVM options:
SET jvmopts=###%jvmopts%###
SET jvmopts=%jvmopts:"###=%
SET jvmopts=%jvmopts:###"=%
SET jvmopts=%jvmopts:###=%

echo Building browse index for %browse%...

set args="%bib_index%" "%field%" "%browse%.tmp"
if "!%3!"=="!1!" goto skipauth
set args="%bib_index%" "%field%" "%auth_index%" "%browse%.tmp"
:skipauth

rem Extract lines from Solr
java %jvmopts% -Dfile.encoding="UTF-8" -Dfield.preferred=heading -Dfield.insteadof=use_for -cp browse-indexing.jar PrintBrowseHeadings %args%

rem Sort lines
sort %browse%.tmp /o sorted-%browse%.tmp

rem Remove duplicate lines
php ..\util\dedupe.php "sorted-%browse%.tmp" "unique-%browse%.tmp"

rem Build database file
java -Dfile.encoding="UTF-8" -cp browse-indexing.jar CreateBrowseSQLite "unique-%browse%.tmp" "%browse%_browse.db"

del /q *.tmp > nul

move "%browse%_browse.db" "%index_dir%\%browse%_browse.db-updated" > nul
echo OK > "%index_dir%\%browse%_browse.db-ready"
:end
