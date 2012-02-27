Combined VuFind enhancement branch for developing VuFind for multiple librares with the same code branch. 

Directory structure: 
conf       - contains configuration information for each server at each library.  Will not be checked into public version control.
covers     - contains cached covers for each library.  Will not be checked into version control.
econtent   - contains eContent files that are served wthin VuFind for each library. Wil not be checked into version control. 
httpd-conf - configuration files for loading the sites into apache for eahc server at each library.  Will not be checked into public version control. 
marc       - contains marc files for each library base on the daily export.  Will not be checked into version control. 
logs       - contains debug log files for each library. Will not be checked into version control
solr       - contains solr index for each library.  Will not be checked into version control. 
vufind     - contains shared vufind code (website, cron, and load tester).  Will be checked in to version control.

