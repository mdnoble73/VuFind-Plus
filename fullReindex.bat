set LIBRARY_NAME=%1
if not "!%LIBRARY_NAME%!"=="!!" goto librarynameset
echo ** ERROR: The name of the library to index should be provided as the first parameter
goto end
:librarynameset
rem ##################################################
rem # Initial setup
rem ##################################################
d:
cd d:/web/tltvufind
rm -f reindexLog.log

java -version
echo Doing Initial Setup >> reindexLog.log 
cp d:/web/tltvufind/solr-data/%VUFIND_HOME%/biblio/conf/schema.xml d:/web/anythink/vufind/solr/biblio2/conf/schema.xml
wget -q "http://localhost:8080/solr/admin/cores?action=RELOAD&core=biblio2"

rem ##################################################
rem # Clear the index for biblio2
rem ##################################################
echo Clearing Index for biblio2 >> reindexLog.log
wget -q "http://localhost:8080/solr/biblio2/update" "--post-data=stream.body=<delete><query>recordtype:marc</query></delete>&commit=true"

rem ##################################################
rem # Reindex biblio2 using the files from the last full export
rem ##################################################
echo Importing all records >> reindexLog.log
for /f %%a IN ('dir /b D:\web\anythink\marc\*.marc') do call import-marc.bat d:/web/anythink/marc/%%a
for /f %%a IN ('dir /b D:\web\anythink\marc\*.mrc') do call import-marc.bat d:/web/anythink/marc/%%a

rem ##################################################
rem # Optimize biblio2
rem ##################################################
echo Optimizing Biblio 2 >> reindexLog.log
wget -q "http://localhost:8080/solr/biblio2/update" "--post-data=stream.body=<optimize />"
echo Optimization Complete >> reindexLog.log

rem ##################################################
rem # create a tar file for backup and optional deploy onto opac1
rem ##################################################
rem echo Creating backup tar file >> reindexLog.log
rem tar -czf web/index.tar.gz /cygdrive/d/web/anythink/vufind/solr/biblio2/index

rem ##################################################
rem # swap the biblio core with the biblio2 core
rem # to get the new data loaded.
rem ##################################################
echo Swapping Cores >> reindexLog.log
wget -q "http://localhost:8080/solr/admin/cores?action=SWAP&core=biblio&other=biblio2"

rem ##################################################
rem # Unload the biblio2 core
rem ##################################################
echo Unloading Biblio 2 core >> reindexLog.log
wget -q "http://localhost:8080/solr/admin/cores?action=UNLOAD&core=biblio2"

rem ##################################################
rem # Copy the index from biblio2 to biblio to make the 
rem # indexes identical.  
rem ##################################################
echo Unloading Biblio core >> reindexLog.log
wget -q "http://localhost:8080/solr/admin/cores?action=UNLOAD&core=biblio"
echo Copying index files to biblio so the cores are identical.  >> reindexLog.log
rm -rf d:\web\anythink\vufind/solr/biblio/index
mkdir d:\web\anythink\vufind\solr\biblio\index
d:
cd d:\web\anythink\vufind\solr\biblio\index
copy d:\web\anythink\vufind\solr\biblio2\index\*.*
cd d:\web\anythink\vufind

wget -q "http://localhost:8080/solr/admin/cores?action=CREATE&name=biblio&instanceDir=d:\web\anythink\vufind\solr\biblio2"

rem ##################################################
rem # Load the biblio2 core with a data directory of 
rem # biblio.  Then we will swap them back.
rem ##################################################
echo Loading new biblio2 core  >> reindexLog.log
wget -q "http://localhost:8080/solr/admin/cores?action=CREATE&name=biblio2&instanceDir=d:\web\anythink\vufind\solr\biblio"

rem ##################################################
rem # Swap cores back so we are consistently running from the biblio data dir at the end of the index
rem ##################################################
echo Swapping cores back  >> reindexLog.log
wget -q "http://localhost:8080/solr/admin/cores?action=SWAP&core=biblio&other=biblio2"

rem ##################################################
rem # Cleanup wget files
rem ##################################################
rm -f cores*
rm -f update
rm -f update.*

end: