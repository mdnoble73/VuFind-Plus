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
}else{
	echo("User-agent: *\r\nDisallow: /\r\n");
}