<?php
include_once 'bootstrap.php';
global $logger;
global $configArray;

require_once('Drivers/marmot_inc/Library.php');
$library = new Library();
$library->find();

ob_start();
echo("<br>Starting to build indexes\r\n");
$logger->log('Starting to alpha browse indexes', PEAR_LOG_INFO);
ob_flush();

set_time_limit(300);
$ret = mysql_query('set @r=0; UPDATE author_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;');
$logger->log('Updated Alpha Rank for Author browse index ret = ' . $ret, PEAR_LOG_INFO);
mysql_query('TRUNCATE author_browse_metadata;');
mysql_query('INSERT INTO author_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)');
echo("<br>Built Author browse index\r\n");
$logger->log('Built Author browse index ret = ' . $ret, PEAR_LOG_INFO);
ob_flush();

set_time_limit(300);
mysql_query('set @r=0; UPDATE title_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;');
$logger->log('Updated Alpha Rank for Title browse index', PEAR_LOG_INFO);
mysql_query('TRUNCATE title_browse_metadata;');
mysql_query('INSERT INTO title_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)');
echo("<br>Built Title browse index\r\n");
$logger->log('Built Title browse index', PEAR_LOG_INFO);
ob_flush();

set_time_limit(300);
mysql_query('set @r=0; UPDATE subject_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;');
$logger->log('Updated Alpha Rank for Subject browse index', PEAR_LOG_INFO);
mysql_query('TRUNCATE subject_browse_metadata;');
mysql_query('INSERT INTO subject_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)');
echo("<br>Built Subject browse index\r\n");
$logger->log('Built Subject browse index', PEAR_LOG_INFO);
ob_flush();

set_time_limit(300);
mysql_query('set @r=0; UPDATE callnumber_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;');
$logger->log('Updated Alpha Rank for Call number browse index', PEAR_LOG_INFO);
mysql_query('TRUNCATE callnumber_browse_metadata;');
mysql_query('INSERT INTO callnumber_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)');
echo("<br>Built Call Number browse index\r\n");
$logger->log('Built Call Number browse index', PEAR_LOG_INFO);
ob_flush();

while ($library->fetch()){
	mysql_query("INSERT INTO title_browse_metadata (SELECT 1, {$library->libraryId}, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results_library_{$library->subdomain} ON id = browseValueId where alphaRank > 0)");
	mysql_query("INSERT INTO author_browse_metadata (SELECT 1, {$library->libraryId}, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results_library_{$library->subdomain} ON id = browseValueId where alphaRank > 0)");
	mysql_query("INSERT INTO subject_browse_metadata (SELECT 1, {$library->libraryId}, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results_library_{$library->subdomain} ON id = browseValueId where alphaRank > 0)");
	mysql_query("INSERT INTO callnumber_browse_metadata (SELECT 1, {$library->libraryId}, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results_library_{$library->subdomain} ON id = browseValueId where alphaRank > 0)");
	echo("<br>Built meta data for {$library->displayName}\r\n");
}

