<?php
/**
 * Dynamic implementation of robots.txt to prevent indexing of non-production sites
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/9/14
 * Time: 11:19 AM
 */

require_once 'bootstrap.php';
global $configArray;
if ($configArray['Site']['isProduction']){
	echo(@file_get_contents('robots.txt'));
	
	$activeLibrary = Library::getActiveLibrary();
	if ($activeLibrary != null){
		$subdomain = $activeLibrary->subdomain;
	
		/*
		 * sitemap: <sitemap_url>
		 * */
		$file = 'robots.txt';
		// Open the file to get existing content
		$current = file_get_contents($file);
		$fileName = $subdomain . '.marmot.org' . '.xml';
		$siteMap_Url = 'sitemap: https' . '://' . $subdomain .'.marmot.org' . '/sitemaps/' .$fileName;
		// Append a new line char to the file
                $current .= "\n";
                //Append the site map index file url
		$current .= $siteMap_Url . "\n";
		// Write the contents back to the file
		file_put_contents($file, $current);
	}
}else {
    echo("User-agent: *\r\nDisallow: /\r\n");
}