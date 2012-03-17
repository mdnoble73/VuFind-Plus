#!/bin/bash
MAILADRES='mark-d-noble@comcast.net';
cd /usr/local/vufind
export VUFIND_HOME='/usr/local/vufind';
rm /usr/local/vufind/marcfiles/fullReindex.log.prev
cp -f /usr/local/vufind/marcfiles/fullReindex.log /usr/local/vufind/marcfiles/fullReindex.log.prev
rm /usr/local/vufind/marcfiles/fullReindex.log
LOG="/usr/local/vufind/marcfiles/fullReindex.log"
exec >> $LOG 2>&1

echo Starting index
date

##################################################
# Copy the files we exported to the last_good_export
##################################################
rm -rf /usr/local/vufind/backup/last_good_export
mkdir /usr/local/vufind/backup/last_good_export
cp /usr/local/vufind/marcfiles/fullexport/*.mrc /usr/local/vufind/backup/last_good_export/

##################################################
# Initial setup of index
##################################################
echo Doing Initial Setup
cp solr/biblio/conf/schema.xml solr/biblio2/conf/schema.xml
wget -q "http://localhost:8080/solr/admin/cores?action=RELOAD&core=biblio2"

##################################################
# Clear the index for biblio2
##################################################
echo Clearing Index for biblio2
wget -q "http://localhost:8080/solr/biblio2/update" "--post-data=stream.body=<delete><query>recordtype:marc</query></delete>&commit=true"

##################################################
# Reindex biblio2 using the files from the last full export
##################################################
echo Importing all records
# Process the current export file
#FILES="/usr/local/vufind/marcfiles/fullexport/*.mrc"
#for f in $FILES
#do
#  echo "Processing $f file..."
#  # take action on each file. $f store current file name
#  /usr/local/vufind/import-marc.sh $f
#done
/usr/local/vufind/import-marc.sh `ls -rt marcfiles/fullexport/*.mrc | tail -1` >> $LOG

##################################################
# Optimize biblio2
##################################################
echo Optimizing Biblio 2
wget -q "http://localhost:8080/solr/biblio2/update" "--post-data=stream.body=<optimize />"
echo Optimization Complete

##################################################
# Pause since optimization seems to not finish consistently which causes failures
##################################################
sleep 300

##################################################
# Optimize stats
##################################################
#echo Optimizing stats
#wget -q "http://localhost:8080/solr/stats/update" "--post-data=stream.body=<optimize />"
#echo Optimization Complete

##################################################
# create a tar file for backup and optional deploy onto opac1
##################################################
echo Creating backup tar file
# Move the last index to the backup folder in case we need to restore. 
mv /usr/local/vufind/web/index.tar.gz /usr/local/vufind/backup/last_good_index.tar.gz
tar -czf /usr/local/vufind/web/index.tar.gz solr/biblio2/index

##################################################
# swap the biblio core with the biblio2 core
# to get the new data loaded.
# biblio will point to biblio 2 at the end of this proceedure.
##################################################
echo Swapping Cores
wget -q "http://localhost:8080/solr/admin/cores?action=SWAP&core=biblio&other=biblio2"

##################################################
# Unload the biblio2 core
##################################################
wget -q "http://localhost:8080/solr/admin/cores?action=UNLOAD&core=biblio2"

##################################################
# Copy the index from biblio2 to biblio to make the 
# indexes identical.  
##################################################
echo Copying index files to biblio so the cores are identical.
rm -rf /usr/local/vufind/solr/biblio/index
mkdir /usr/local/vufind/solr/biblio/index
cd /usr/local/vufind/solr/biblio
cp -rp /usr/local/vufind/solr/biblio2/index .

rm -rf /usr/local/vufind/solr/biblio/spellchecker
mkdir /usr/local/vufind/solr/biblio/spellchecker
cd /usr/local/vufind/solr/biblio
cp -rp /usr/local/vufind/solr/biblio2/spellchecker .

rm -rf /usr/local/vufind/solr/biblio/spellShingle
mkdir /usr/local/vufind/solr/biblio/spellShingle
cd /usr/local/vufind/solr/biblio
cp -rp /usr/local/vufind/solr/biblio2/spellShingle .

cd /usr/local/vufind

##################################################
# Load the biblio2 core with a data directory of 
# biblio.  Then we will swap them back.
##################################################
echo Loading new biblio2 core
wget -q "http://localhost:8080/solr/admin/cores?action=CREATE&name=biblio2&instanceDir=/usr/local/vufind/solr/biblio&dataDir=/usr/local/vufind/solr/biblio"
echo swapping cores back
wget -q "http://localhost:8080/solr/admin/cores?action=SWAP&core=biblio&other=biblio2"

##################################################
# Cleanup wget files
##################################################
rm -f cores*
rm -f update
rm -f update.*

##################################################
# restart Vufind to ensure memory leaks etc don't bog down the system.
##################################################
/usr/local/vufind/vufind.sh restart

echo Finished Full Reindex
date
echo "Full Reindex of Anythink server Finished" | /usr/sbin/sendmail $MAILADRES