<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/** CORE APPLICATION CONTROLLER **/
$startTime = microtime(true);
// Retrieve values from configuration file
require_once 'sys/ConfigArray.php';
$configArray = readConfig();
require_once 'sys/Timer.php';
global $timer;
$timer = new Timer($startTime);
$timer->logTime("Read Config");

if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}

global $memcache;
// Set defaults if nothing set in config file.
$host = isset($configArray['Caching']['memcache_host']) ? $configArray['Caching']['memcache_host'] : 'localhost';
$port = isset($configArray['Caching']['memcache_port']) ? $configArray['Caching']['memcache_port'] : 11211;
$timeout = isset($configArray['Caching']['memcache_connection_timeout']) ? $configArray['Caching']['memcache_connection_timeout'] : 1;

// Connect to Memcache:
$memcache = new Memcache();
if (!$memcache->pconnect($host, $port, $timeout)) {
	PEAR::raiseError(new PEAR_Error("Could not connect to Memcache (host = {$host}, port = {$port})."));
}
$timer->logTime("Initialize Memcache");

//Cleanup method information so module, action, and id are set properly.
//This ensures that we don't have to change the http-vufind.conf file when new types are added.
//$dataObjects = array('Record', 'EcontentRecord', 'EContent', 'EditorialReview', 'Person');
//$dataObjectsStr = implode('|', $dataObjects);
//Deal with old path based urls by removing the leading path.
$requestURI = $_SERVER['REQUEST_URI'];
$requestURI = preg_replace("/^\/?vufind\//", "", $requestURI);
if (preg_match("/(MyResearch|MyAccount)\/([^\/?]+)\/([^\/?]+)(\?.+)?/", $requestURI, $matches)){
	$_GET['module'] = $matches[1];
	$_GET['id'] = $matches[3];
	$_GET['action'] = $matches[2];
	$_REQUEST['module'] = $matches[1];
	$_REQUEST['id'] = $matches[3];
	$_REQUEST['action'] = $matches[2];
}elseif (preg_match("/(MyResearch|MyAccount)\/([^\/?]+)(\?.+)?/", $requestURI, $matches)){
	$_GET['module'] = $matches[1];
	$_GET['action'] = $matches[2];
	$_REQUEST['id'] = '';
	$_REQUEST['module'] = $matches[1];
	$_REQUEST['action'] = $matches[2];
	$_REQUEST['id'] = '';
}elseif (preg_match("/([^\/?]+)\/((?:\.b)?\d+x?)\/([^\/?]+)/", $requestURI, $matches)){
	$_GET['module'] = $matches[1];
	$_GET['id'] = $matches[2];
	$_GET['action'] = $matches[3];
	$_REQUEST['module'] = $matches[1];
	$_REQUEST['id'] = $matches[2];
	$_REQUEST['action'] = $matches[3];
}elseif (preg_match("/([^\/?]+)\/((?:\.b)?\d+x?)/", $requestURI, $matches)){
	$_GET['module'] = $matches[1];
	$_GET['id'] = $matches[2];
	$_GET['action'] = 'Home';
	$_REQUEST['module'] = $matches[1];
	$_REQUEST['id'] = $matches[2];
	$_REQUEST['action'] = 'Home';
}elseif (preg_match("/([^\/?]+)\/([^\/?]+)/", $requestURI, $matches)){
	$_GET['module'] = $matches[1];
	$_GET['action'] = $matches[2];
	$_REQUEST['module'] = $matches[1];
	$_REQUEST['action'] = $matches[2];
}
if (isset($_GET['module']) && $_GET['module'] == 'MyAccount'){
	$_GET['module'] = 'MyResearch';
	$_REQUEST['module'] = 'MyResearch';
}
// Try to set the locale to UTF-8, but fail back to the exact string from the config
// file if this doesn't work -- different systems may vary in their behavior here.
setlocale(LC_MONETARY, array($configArray['Site']['locale'] . ".UTF-8",
$configArray['Site']['locale']));
date_default_timezone_set($configArray['Site']['timezone']);

// Require System Libraries
require_once 'PEAR.php';
$timer->logTime("Include PEAR");
require_once 'sys/Interface.php';
$timer->logTime("Include Interface");
require_once 'sys/Logger.php';
$timer->logTime("Include Logger");
require_once 'sys/User.php';
$timer->logTime("Include User");
require_once 'sys/Translator.php';
$timer->logTime("Include Translator");
require_once 'sys/SearchObject/Factory.php';
$timer->logTime("Include Search Object Factory");
require_once 'sys/ConnectionManager.php';
$timer->logTime("Include ConnectionManager");
require_once 'Drivers/marmot_inc/Library.php';
require_once 'Drivers/marmot_inc/Location.php';
$timer->logTime("Include Library and Location");
require_once 'sys/UsageTracking.php';
$timer->logTime("Include Usage Tracking");

$timer->logTime('Startup');
// Set up autoloader (needed for YAML)
function vufind_autoloader($class) {
	if (file_exists('sys/' . $class . '.php')){
		require_once 'sys/' . $class . '.php';
	}elseif (file_exists('services/MyResearch/lib/' . $class . '.php')){
		require_once 'services/MyResearch/lib/' . $class . '.php';
	}else{
		require_once str_replace('_', '/', $class) . '.php';
	}
}
spl_autoload_register('vufind_autoloader');

// Sets global error handler for PEAR errors
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'handlePEARError');

// Sets global error handler for PHP errors
//set_error_handler('handlePHPError');

// Setup Local Database Connection
define('DB_DATAOBJECT_NO_OVERLOAD', 0);
$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
$options = $configArray['Database'];
$timer->logTime('Setup database connection');

// Initiate Session State
$session_type = $configArray['Session']['type'];
$session_lifetime = $configArray['Session']['lifetime'];
$session_rememberMeLifetime = $configArray['Session']['rememberMeLifetime'];
register_shutdown_function('session_write_close');
if (isset($configArray['Site']['cookie_domain'])){
	session_set_cookie_params(0, '/', $configArray['Site']['cookie_domain']);
}
require_once 'sys/' . $session_type . '.php';
if (class_exists($session_type)) {
	$session = new $session_type();
	$session->init($session_lifetime, $session_rememberMeLifetime);
}
$timer->logTime('Session initialization ' . $session_type);

//Create global singleton instances for Library and Location
global $librarySingleton;
$librarySingleton = new Library();
$timer->logTime('Created library');
global $locationSingleton;
$locationSingleton = new Location();
$timer->logTime('Created Location');

$active_ip = $locationSingleton->getActiveIp();
if (!isset($_COOKIE['test_ip']) || $active_ip != $_COOKIE['test_ip']){
	if ($active_ip == ''){
		setcookie('test_ip', $active_ip, time() - 1000, '/');
	}else{
		setcookie('test_ip', $active_ip, 0, '/');
	}
}
$timer->logTime('Got active ip address');
$branch = $locationSingleton->getBranchLocationCode();
if (!isset($_COOKIE['branch']) || $branch != $_COOKIE['branch']){
	if ($branch == ''){
		setcookie('branch', $branch, time() - 1000, '/');
	}else{
		setcookie('branch', $branch, 0, '/');
	}
}
$timer->logTime('Got branch');

//Update configuration information for scoping now that the database is setup.
$configArray = updateConfigForScoping($configArray);
$timer->logTime('Updated config for scoping');

//Now check the Active Location to see if there is an override for the facets.
$configArray = updateConfigForActiveLocation($configArray);
$timer->logTime('Updated config for active location');

// Start Interface
$interface = new UInterface();
$timer->logTime('Create interface');
if (isset($configArray['Site']['theme_css'])){
	$interface->assign('theme_css', $configArray['Site']['theme_css']);
}
$interface->assign('smallLogo', $configArray['Site']['smallLogo']);
$interface->assign('largeLogo', $configArray['Site']['largeLogo']);
//Set focus to the search box by default.
$interface->assign('focusElementId', 'lookfor');

//Get the name of the active instance
if ($locationSingleton->getActiveLocation() != null){
	$interface->assign('librarySystemName', $locationSingleton->getActiveLocation()->displayName);
}elseif (isset($library)){
	$interface->assign('librarySystemName', $library->displayName);
}else{
	$interface->assign('librarySystemName', 'Marmot');
}
if ($locationSingleton->getIPLocation() != null){
	$interface->assign('inLibrary', true);
}else{
	$interface->assign('inLibrary', false);
}

$productionServer = $configArray['Site']['isProduction'];
$interface->assign('productionServer', $productionServer);

global $library;
//Set default Ask a Librarian link
if (isset($library) && strlen($library->askALibrarianLink) > 0){
	$interface->assign('askALibrarianLink', $library->askALibrarianLink);
}else{
	$interface->assign('askALibrarianLink', 'http://www.askcolorado.org/');
}
if (isset($library) && strlen($library->illLink) > 0){
	$interface->assign('illLink', $library->illLink);
}
if (isset($library) && strlen($library->suggestAPurchase) > 0){
	$interface->assign('suggestAPurchaseLink', $library->suggestAPurchase);
}

if (isset($library) && strlen($library->boopsieLink) > 0){
	$interface->assign('boopsieLink', $library->boopsieLink);
}

$location = $locationSingleton->getActiveLocation();
if (isset($location) && strlen($location->homeLink) > 0 && $location->homeLink != 'default'){
	$interface->assign('homeLink', $location->homeLink);
}elseif (isset($library) && strlen($library->homeLink) > 0 && $library->homeLink != 'default'){
	$interface->assign('homeLink', $library->homeLink);
}
if (isset($library)){
	$interface->assign('showLoginButton', $library->showLoginButton);
	$interface->assign('showAdvancedSearchbox', $library->showAdvancedSearchbox);
	$interface->assign('enableBookCart', $library->enableBookCart);
}else{
	$interface->assign('showLoginButton', 1);
	$interface->assign('showAdvancedSearchbox', 1);
	$interface->assign('enableBookCart', 1);
}
$timer->logTime('Interface checks for library and location');

//Set that the interface is a single column by default
$interface->assign('page_body_style', 'one_column');

if (isset($configArray['Strands']) && isset($configArray['Strands']['APID']) && strlen($configArray['Strands']['APID']) > 0){
	$interface->assign('strandsAPID', $configArray['Strands']['APID']);
	$interface->assign('showStrands', true);
}else{
	$interface->assign('showStrands', false);
}
$interface->assign('showPackagingDetailsReport', isset($configArray['EContent']['showPackagingDetailsReport']) && $configArray['EContent']['showPackagingDetailsReport']);
$interface->assign('showFines', $configArray['Catalog']['showFines']);

// Check system availability
$mode = checkAvailabilityMode();
if ($mode['online'] === false) {
	// Why are we offline?
	switch ($mode['level']) {
		// Forced Downtime
		case "unavailable":
			// TODO : Variable reasons, and translated
			//$interface->assign('message', $mode['message']);
			$interface->display($mode['template']);
			break;

			// Should never execute. checkAvailabilityMode() would
			//    need to know we are offline, but not why.
		default:
			// TODO : Variable reasons, and translated
			//$interface->assign('message', $mode['message']);
			$interface->display($mode['template']);
			break;
	}
	exit();
}
$timer->logTime('Checked availability mode');

//Check to see if we have a collection applied.
global $defaultCollection;
if (isset($_GET['collection'])){
	$defaultCollection = $_GET['collection'];
	//Set a coookie so we don't have to transfer the ip from page to page.
	if ($defaultCollection == '' || $defaultCollection == 'all'){
		setcookie('collection', '', 0, '/');
		$defaultCollection = null;
	}else{
		setcookie('collection', $defaultCollection, 0, '/');
	}
}elseif (isset($_COOKIE['collection'])){
	$defaultCollection = $_COOKIE['collection'];
}else{
	//No collection has been set.
}
$timer->logTime('Check default collection');

// Proxy server settings
if (isset($configArray['Proxy']['host'])) {
	if (isset($configArray['Proxy']['port'])) {
		$proxy_server = $configArray['Proxy']['host'].":".$configArray['Proxy']['port'];
	} else {
		$proxy_server = $configArray['Proxy']['host'];
	}
	$proxy = array('http' => array('proxy' => "tcp://$proxy_server", 'request_fulluri' => true));
	stream_context_get_default($proxy);
}
$timer->logTime('Proxy server checks');

// Setup Translator
global $language;
global $servername;
if (isset($_REQUEST['mylang'])) {
	$language = strip_tags($_REQUEST['mylang']);
	setcookie('language', $language, null, '/');
} else {
	$language = strip_tags((isset($_COOKIE['language'])) ? $_COOKIE['language'] : $configArray['Site']['language']);
}
$translator = $memcache->get("translator_{$servername}_{$language}");
if ($translator == false){
	// Make sure language code is valid, reset to default if bad:
	$validLanguages = array_keys($configArray['Languages']);
	if (!in_array($language, $validLanguages)) {
		$language = $configArray['Site']['language'];
	}
	$translator = new I18N_Translator('lang', $language, $configArray['System']['missingTranslations']);
	$memcache->set("translator_{$servername}_{$language}", $translator, 0, $configArray['Caching']['translator']);
	$timer->logTime('Translator setup');
}
$interface->setLanguage($language);

// Determine Module and Action
global $user;
$user = UserAccount::isLoggedIn();
$timer->logTime('Check if user is logged in');
$module = (isset($_GET['module'])) ? $_GET['module'] : null;
$module = preg_replace('/[^\w]/', '', $module);
$action = (isset($_GET['action'])) ? $_GET['action'] : null;
$action = preg_replace('/[^\w]/', '', $action);
//Set these initially in case user login fails, we will need the module to be set.
$interface->assign('module', $module);
$interface->assign('action', $action);

//Determine whether or not materials request functionality should be enabled
require_once 'sys/MaterialsRequest.php';
$interface->assign('enableMaterialsRequest', MaterialsRequest::enableMaterialsRequest());

// Process Authentication, must be done here so we can redirect based on user information
// immediately after logging in.
if ($user) {
	$interface->assign('user', $user);
	//Create a cookie for the user's home branch so we can sort holdings even if they logout.
	//Cookie expires in 1 week.
	setcookie('home_location', $user->homeLocationId, time()+60*60*24*7, '/');

} else if (// Special case for Shibboleth:
($configArray['Authentication']['method'] == 'Shibboleth' && $module == 'MyResearch') ||
// Default case for all other authentication methods:
((isset($_POST['username']) && isset($_POST['password'])) && ($action != 'Account' && $module != 'AJAX'))) {
	$user = UserAccount::login();
	if (PEAR::isError($user)) {
		require_once 'services/MyResearch/Login.php';
		Login::launch($user->getMessage());
		exit();
	}
	$interface->assign('user', $user);
	//Check to see if there is a followup module and if so, use that module and action for the next page load
	if (isset($_REQUEST['returnUrl'])) {
		$followupUrl =  $_REQUEST['returnUrl'];
		header("Location: " . $followupUrl);
		exit();
	}
	if ($user){
		if (isset($_REQUEST['followupModule']) && isset($_REQUEST['followupAction'])) {
			echo("Redirecting to followup location");
			$followupUrl =  $configArray['Site']['url'] . "/".  strip_tags($_REQUEST['followupModule']);
			if (!empty($_REQUEST['recordId'])) {
				$followupUrl .= "/" .  strip_tags($_REQUEST['recordId']);
			}
			$followupUrl .= "/" .  strip_tags($_REQUEST['followupAction']);
			if(isset($_REQUEST['comment'])) $followupUrl .= "?comment=" . urlencode($_REQUEST['comment']);
			header("Location: " . $followupUrl);
			exit();
		}
	}
	//TODO: Redirect to that location?
	if (isset($_REQUEST['followup']) || isset($_REQUEST['followupModule'])){
		$module = isset($_REQUEST['followupModule']) ? $_REQUEST['followupModule'] : $configArray['Site']['defaultModule'];
		$action = isset($_REQUEST['followup']) ? $_REQUEST['followup'] : (isset($_REQUEST['followupAction']) ? $_REQUEST['followupAction'] : 'Home');
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		}elseif (isset($_REQUEST['recordId'])){
			$id = $_REQUEST['recordId'];
		}
		if (isset($id)){
			$_REQUEST['id'] = $id;
		}
		$_REQUEST['module'] = $module;
		$_REQUEST['action'] = $action;
	}
}
$timer->logTime('User authentication');

//Find a resonable default location to go to
if ($module == null && $action == null){
	//We have no information about where to go, go to the default location from config
	$module = $configArray['Site']['defaultModule'];
	$action = 'Home';

}elseif ($action == null){
	$action = 'Home';
}

$interface->assign('module', $module);
$interface->assign('action', $action);
$timer->logTime('Load module and action');

//Determine the Search Source, need to do this always.
if (isset($_GET['searchSource'])){
	$searchSource = $_GET['searchSource'];
	$_REQUEST['searchSource'] = $searchSource; //Update request since other check for it here
	$_SESSION['searchSource'] = $searchSource; //Update the session so we can remember what the user was doing last.
}else{
	if ( isset($_SESSION['searchSource'])){ //Didn't get a source, use what the user was doing last
		$searchSource = $_SESSION['searchSource'];
		$_REQUEST['searchSource'] = $searchSource;
	}else{
		//Use a default search source
		if ($module == 'Person'){
			$searchSource = 'genealogy';
		}else{
			$searchSource = 'local';
		}
		$_REQUEST['searchSource'] = $searchSource;
	}
}
if (isset($_REQUEST['basicType'])){
	$interface->assign('basicSearchIndex', $_REQUEST['basicType']);
}else{
	$interface->assign('basicSearchIndex', 'Keyword');
}
if (isset($_REQUEST['genealogyType'])){
	$interface->assign('genealogySearchIndex', $_REQUEST['genealogyType']);
}else{
	$interface->assign('genealogySearchIndex', 'GenealogyKeyword');
}
if ($searchSource == 'genealogy'){
	$_REQUEST['type'] = isset($_REQUEST['genealogyType']) ? $_REQUEST['genealogyType']:  'GenealogyKeyword';
}else{
	$_REQUEST['type'] = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : 'Keyword';
}
$interface->assign('searchSource', $searchSource);

//Determine if the top search box and breadcrumbs should be shown.  Not showing these
//Does have a slight performance advantage.
if ($action == "AJAX" || $action == "JSON"){
	$interface->assign('showTopSearchBox', 0);
	$interface->assign('showBreadcrumbs', 0);
}else{
	if (isset($configArray['FooterLists'])){
		$interface->assign('footerLists', $configArray['FooterLists']);
	}
	
	//Load basic search types for use in the interface.
	$searchObject = SearchObjectFactory::initSearchObject();
	$searchObject->init();
	$timer->logTime('Create Search Object');
	//Add browse types as well.
	$includeAlphaBrowse = true;
	if (isset($library) && $library->enableAlphaBrowse == false){
		$includeAlphaBrowse = false;
	}
	if ($interface->isMobile()){
		$includeAlphaBrowse = false;
	}
	$basicSearchTypes = is_object($searchObject) ?    $searchObject->getBasicTypes() : array();
	if ($includeAlphaBrowse){
		$basicSearchTypes = array_merge($basicSearchTypes, $searchObject->getBrowseTypes());
	}
	$interface->assign('basicSearchTypes', $basicSearchTypes);

	//Load repeat search options
	require_once('Drivers/marmot_inc/SearchSources.php');
	$searchSources = new SearchSources();
	$interface->assign('searchSources', $searchSources->getSearchSources());

	if (isset($configArray['Genealogy'])){
		//Do not allow genealogy search in mobile theme
		$genealogySearchObject = SearchObjectFactory::initSearchObject('Genealogy');
		$interface->assign('genealogySearchTypes', is_object($genealogySearchObject) ? $genealogySearchObject->getBasicTypes() : array());
	}

	if (!($module == 'Search' && $action == 'Home')){
		$savedSearch = $searchObject->loadLastSearch();
		//Load information about the search so we can display it in the search box
		if (!is_null($savedSearch)){
			$interface->assign('lookfor',             $savedSearch->displayQuery());
			$interface->assign('searchType',          $savedSearch->getSearchType());
			$interface->assign('searchIndex',         $savedSearch->getSearchIndex());
			$interface->assign('filterList', $savedSearch->getFilterList());
		}
		$timer->logTime('Load last search for redisplay');
	}

	if ((($module=="Search" || $module=="Summon" || $module=="WorldCat") && $action =="Home") ||
	$action == "AJAX" || $action == "JSON"){
		$interface->assign('showTopSearchBox', 0);
		$interface->assign('showBreadcrumbs', 0);
	}else{
		$interface->assign('showTopSearchBox', 1);
		$interface->assign('showBreadcrumbs', 1);
		if (isset($library) && $library != false && $library->useHomeLinkInBreadcrumbs){
			$interface->assign('homeBreadcrumbLink', $library->homeLink);
		}else{
			$interface->assign('homeBreadcrumbLink', $interface->getUrl());
		}
	}
	//Load user list for book bag
	if ($user){
		$lists = $user->getLists();
		$timer->logTime('Get user lists for book cart');
		
		$userLists = array();
		foreach($lists as $current) {
			$userLists[] = array('id' => $current->id,
                    'title' => $current->title);
		}
		$interface->assign('userLists', $userLists);
	}
}

//Determine if we should include autoLogout Code
$ipLocation = $locationSingleton->getIPLocation();
$ipId = $locationSingleton->getIPid();

if (!is_null($ipLocation) && $user){
	$interface->assign('onInternalIP', true);
	if (isset($user->bypassAutoLogout) && $user->bypassAutoLogout == 1){
		$interface->assign('includeAutoLogoutCode', false);
	}else{
		$includeAutoLogoutCode = true;
		//Get the PType for the user
		$catalog = new CatalogConnection($configArray['Catalog']['driver']);
		if ($catalog->checkFunction('isUserStaff')){
			$userIsStaff = $catalog->isUserStaff();
			$interface->assign('userIsStaff', $userIsStaff);
			if ($userIsStaff){
				//Check to see if the user has overridden the auto logout code.
				if ($user->bypassAutoLogout != 0){
					$includeAutoLogoutCode = false;
				}
			}
		}
		
		$interface->assign('includeAutoLogoutCode', $includeAutoLogoutCode);
	}
}else{
	$interface->assign('onInternalIP', false);
	$interface->assign('includeAutoLogoutCode', false);
}
$timer->logTime('Check whether or not to include auto logout code');

if (!in_array($action, array("AJAX", "JSON")) && !in_array($module, array("API", "Admin", "Report")) ){
	// Log the usageTracking data
	$usageTracking = new UsageTracking();
	$usageTracking->logTrackingData('numPageViews', 1, $ipLocation, $ipId);
	$timer->logTime('Log Usage Tracking');
}

// Process Login Followup
if (isset($_REQUEST['followup'])) {
	processFollowup();
	$timer->logTime('Process followup');	
}

//If there is a hold_message, make sure it gets displayed.
if (isset($_SESSION['hold_message'])) {
	$interface->assign('hold_message', formatHoldMessage($_SESSION['hold_message']));
	unset($_SESSION['hold_message']);
}elseif (isset($_SESSION['renew_message'])){
	$interface->assign('renew_message', formatRenewMessage($_SESSION['renew_message']));
	unset($_SESSION['renew_message']);
}

// Process Solr shard settings
processShards();
$timer->logTime('Process Shards');

// Call Action
if (is_readable("services/$module/$action.php")) {
	require_once "services/$module/$action.php";
	if (class_exists($action)) {
		$service = new $action();
		$timer->logTime('Start lauch of action');
		$service->launch();
		$timer->logTime('Finish launch of action');
	} else {
		PEAR::raiseError(new PEAR_Error('Unknown Action'));
	}
} else {
	PEAR::RaiseError(new PEAR_Error("Cannot Load Action '$action' for Module '$module'"));
}
$timer->logTime('Finished Index');
$timer->writeTimings();

function processFollowup(){
	global $configArray;

	switch($_REQUEST['followup']) {
		case 'SaveRecord':
			$result = file_get_contents($configArray['Site']['url'] .
                    "/Record/AJAX?method=SaveRecord&id=" . urlencode($_REQUEST['id']));
			break;
		case 'SaveTag':
			$result = file_get_contents($configArray['Site']['url'] .
                    "/Record/AJAX?method=SaveTag&id=" . urlencode($_REQUEST['id']) .
                    "&tag=" . urlencode($_REQUEST['tag']));
			break;
		case 'SaveComment':
			$result = file_get_contents($configArray['Site']['url'] .
                    "/Record/AJAX?method=SaveComment&id=" . urlencode($_REQUEST['id']) .
                    "&comment=" . urlencode($_REQUEST['comment']));
			break;
		case 'SaveSearch':
			header("Location: {$configArray['Site']['url']}/".$_REQUEST['followupModule']."/".$_REQUEST['followupAction']."?".$_REQUEST['recordId']);
			die();
			break;
	}
}

/**
 * Process Solr-shard-related parameters and settings.
 *
 * @return void
 */
function processShards()
{
	global $configArray;
	global $interface;

	// If shards are not configured, give up now:
	if (!isset($configArray['IndexShards']) || empty($configArray['IndexShards'])) {
		return;
	}

	// If a shard selection list is found as an incoming parameter, we should save
	// it in the session for future reference:
	if (array_key_exists('shard', $_REQUEST)) {
		$_SESSION['shards'] = $_REQUEST['shard'];
	} else if (!array_key_exists('shards', $_SESSION)) {
		// If no selection list was passed in, use the default...

		// If we have a default from the configuration, use that...
		if (isset($configArray['ShardPreferences']['defaultChecked'])
		&& !empty($configArray['ShardPreferences']['defaultChecked'])
		) {
			$checkedShards = $configArray['ShardPreferences']['defaultChecked'];
			$_SESSION['shards'] = is_array($checkedShards) ?
			$checkedShards : array($checkedShards);
		} else {
			// If no default is configured, use all shards...
			$_SESSION['shards'] = array_keys($configArray['IndexShards']);
		}
	}

	// If we are configured to display shard checkboxes, send a list of shards
	// to the interface, with keys being shard names and values being a boolean
	// value indicating whether or not the shard is currently selected.
	if (isset($configArray['ShardPreferences']['showCheckboxes'])
	&& $configArray['ShardPreferences']['showCheckboxes'] == true
	) {
		$shards = array();
		foreach ($configArray['IndexShards'] as $shardName => $shardAddress) {
			$shards[$shardName] = in_array($shardName, $_SESSION['shards']);
		}
		$interface->assign('shards', $shards);
	}
}

function disableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled = false;
}
function enableErrorHandler(){
	global $errorHandlingEnabled;
	$errorHandlingEnabled = true;
}

// Process any errors that are thrown
function handlePEARError($error, $method = null){
	global $errorHandlingEnabled;
	if (isset($errorHandlingEnabled) && $errorHandlingEnabled == false){
		return;
	}
	global $configArray;

	// It would be really bad if an error got raised from within the error handler;
	// we would go into an infinite loop and run out of memory.  To avoid this,
	// we'll set a static value to indicate that we're inside the error handler.
	// If the error handler gets called again from within itself, it will just
	// return without doing anything to avoid problems.  We know that the top-level
	// call will terminate execution anyway.
	static $errorAlreadyOccurred = false;
	if ($errorAlreadyOccurred) {
		return;
	} else {
		$errorAlreadyOccurred = true;
	}

	//Clear any output that has been generated so far so the user just gets the error message.
	if (!$configArray['System']['debug']){ 
		@ob_clean();
		header("Content-Type: text/html");
	}
	
	// Display an error screen to the user:
	global $interface;
	if (!isset($interface) || $interface == false){
		$interface = new UInterface();
	}

	$interface->assign('error', $error);
	$interface->assign('debug', $configArray['System']['debug']);
	$interface->display('error.tpl');

	// Exceptions we don't want to log
	$doLog = true;
	// Microsoft Web Discussions Toolbar polls the server for these two files
	//    it's not script kiddie hacking, just annoying in logs, ignore them.
	if (strpos($_SERVER['REQUEST_URI'], "cltreq.asp") !== false) $doLog = false;
	if (strpos($_SERVER['REQUEST_URI'], "owssvr.dll") !== false) $doLog = false;
	// If we found any exceptions, finish here
	if (!$doLog) exit();

	// Log the error for administrative purposes -- we need to build a variety
	// of pieces so we can supply information at five different verbosity levels:
	$baseError = $error->toString();
	$basicServer = " (Server: IP = {$_SERVER['REMOTE_ADDR']}, " .
        "Referer = " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ", " .
        "User Agent = {$_SERVER['HTTP_USER_AGENT']}, " .
        "Request URI = {$_SERVER['REQUEST_URI']})";
	$detailedServer = "\nServer Context:\n" . print_r($_SERVER, true);
	$basicBacktrace = "\nBacktrace:\n";
	if (is_array($error->backtrace)) {
		foreach($error->backtrace as $line) {
			$basicBacktrace .= "{$line['file']} line {$line['line']} - " .
                "class = {$line['class']}, function = {$line['function']}\n";
		}
	}
	$detailedBacktrace = "\nBacktrace:\n" . print_r($error->backtrace, true);
	$errorDetails = array(
	1 => $baseError,
	2 => $baseError . $basicServer,
	3 => $baseError . $basicServer . $basicBacktrace,
	4 => $baseError . $detailedServer . $basicBacktrace,
	5 => $baseError . $detailedServer . $detailedBacktrace
	);

	$logger = new Logger();
	$logger->log($errorDetails, PEAR_LOG_ERR);

	exit();
}

// Check for the various stages of functionality
function checkAvailabilityMode() {
	global $configArray;
	global $locationSingleton;
	$mode = array();

	// If the config file 'available' flag is
	//    set we are forcing downtime.
	if (!$configArray['System']['available']) {
		//Unless the user is accessing from a maintainence IP address
		
		$isMaintainence = false;
		if (isset($configArray['System']['maintainenceIps'])){
			$activeIp = $locationSingleton->getActiveIp();
			$maintainenceIp =  $configArray['System']['maintainenceIps'];
			
			$maintainenceIps = explode(",", $maintainenceIp);
			foreach ($maintainenceIps as $curIp){
				if ($curIp == $activeIp){
					$isMaintainence = true;
					break;
				}
			}
		}
		
		if (!$isMaintainence){
			$mode['online']   = false;
			$mode['level']    = 'unavailable';
			// TODO : Variable reasons passed to template... and translated
			//$mode['message']  = $configArray['System']['available_reason'];
			$mode['template'] = 'unavailable.tpl';
			return $mode;
		}
	}
	// TODO : Check if solr index is online
	// TODO : Check if ILMS database is online
	// TODO : More?

	// No problems? We are online then
	$mode['online'] = true;
	return $mode;
}

/**
 * Update the configuration array as needed based on scoping rules defined
 * by the subdomain.
 *
 * @param array $configArray the existing main configuration options.
 *
 * @return array the configuration options adjusted based on the scoping rules.
 */
function updateConfigForScoping($configArray) {
	global $timer;
	//Get the subdomain for the request
	global $servername;

	//Default dynamic logos
	$configArray['Site']['smallLogo'] = "/interface/themes/{$configArray['Site']['theme']}/images/logo_small.png";
	$configArray['Site']['largeLogo'] = "/interface/themes/{$configArray['Site']['theme']}/images/logo_large.png";

	//split the servername based on
	$subdomain = null;
	if(strpos($_SERVER['SERVER_NAME'], '.')){
		$serverComponents = explode('.', $_SERVER['SERVER_NAME']);
		if (count($serverComponents) >= 3){
			//URL is probably of the form subdomain.marmot.org or subdomain.opac.marmot.org
			$subdomain = $serverComponents[0];
		} else if (count($serverComponents) == 2){
			//URL could be either subdomain.localhost or marmot.org. Only use the subdomain
			//If the second component is localhost.
			if (strcasecmp($serverComponents[1], 'localhost') == 0){
				$subdomain = $serverComponents[0];
			}
		}
	}
	
	$timer->logTime('got subdomain');

	//Load the library system information
	global $library;
	global $locationSingleton;
	if (isset($_SESSION['library']) && isset($_SESSION['location'])){
		$library = $_SESSION['library'];
		$locationSingleton = $_SESSION['library'];
	}else{
		$Library = new Library();
		$Library->whereAdd("subdomain = '$subdomain'");
		$Library->find();
		
	
		if ($Library->N == 1) {
			$Library->fetch();
			//Make the library infroamtion global so we can work with it later.
			$library = $Library;
		}else{
			//The subdomain can also indicate a location.
			$Location = new Location();
			$Location->whereAdd("code = '$subdomain'");
			$Location->find();
			if ($Location->N == 1){
				$Location->fetch();
				//We found a location for the subdomain, get the library.
				global $librarySingleton;
				$library = $librarySingleton->getLibraryForLocation($Location->locationId);
				$locationSingleton->setActiveLocation(clone $Location);
			}
		}
	}
	if (isset($library) && $library != null){
		//Update the title
		$configArray['Site']['theme'] = $library->themeName . ',' . $configArray['Site']['theme'] . ',default';
		$configArray['Site']['title'] = $library->displayName;
		//Update the facets file
		if (strlen($library->facetFile) > 0 && $library->facetFile != 'default'){
			$file = trim("../../sites/$servername/conf/facets/" . $library->facetFile . '.ini');
			if (file_exists($file)) {
				$configArray['Extra_Config']['facets'] = 'facets/' . $library->facetFile . '.ini';
			}
		}
		
		//Update the searches file
		if (strlen($library->searchesFile) > 0 && $library->searchesFile != 'default'){
			$file = trim("../../sites/$servername/conf/searches/" . $library->searchesFile . '.ini');
			if (file_exists($file)) {
				$configArray['Extra_Config']['searches'] = 'searches/' . $library->searchesFile . '.ini';
			}
		}
		

		$location = $locationSingleton->getActiveLocation();
		
		//Add an extra css file for the scope if it exists.
		if (file_exists('./interface/themes/' . $library->themeName . '/css/extra_styles.css')) {
			$configArray['Site']['theme_css'] = $configArray['Site']['url'] . '/interface/themes/' . $library->themeName . '/css/extra_styles.css';
		}
		if ($location != null && file_exists('./interface/themes/' . $library->themeName . '/css/'. $location->code .'_extra_styles.css')) {
			$configArray['Site']['theme_css'] = $configArray['Site']['url'] . '/interface/themes/' . $library->themeName . '/css/'. $location->code .'_extra_styles.css';
		}
		if ($location != null && file_exists('./interface/themes/' . $library->themeName . '/images/'. $location->code .'_logo_small.png')) {
			$configArray['Site']['smallLogo'] = '/interface/themes/' . $library->themeName . '/images/'. $location->code .'_logo_small.png';
		}elseif (file_exists('./interface/themes/' . $library->themeName . '/images/logo_small.png')) {
			$configArray['Site']['smallLogo'] = '/interface/themes/' . $library->themeName . '/images/logo_small.png';
		}else{
			$configArray['Site']['smallLogo'] = "/interface/themes/{$configArray['Site']['theme']}/images/logo_small.png";
		}
		if ($location != null && file_exists('./interface/themes/' . $library->themeName . '/images/'. $location->code .'_logo_large.png')) {
			$configArray['Site']['largeLogo'] = '/interface/themes/' . $library->themeName . '/images/'. $location->code .'_logo_large.png';
		}elseif (file_exists('./interface/themes/' . $library->themeName . '/images/logo_large.png')) {
			$configArray['Site']['largeLogo'] = '/interface/themes/' . $library->themeName . '/images/logo_large.png';
		}else{
			$configArray['Site']['largeLogo'] = "/interface/themes/{$configArray['Site']['theme']}/images/logo_large.png";
		}

	}
	$timer->logTime('finished update config for scoping');

	return $configArray;
}

function updateConfigForActiveLocation($configArray){
	global $locationSingleton;
	$location = $locationSingleton->getActiveLocation();
	if ($location != null){
		if (strlen($location->facetFile) > 0 && $location->facetFile != 'default'){
			$file = trim('../../conf/facets/' . $location->facetFile . '.ini');
			if (file_exists($file)) {
				$configArray['Extra_Config']['facets'] = 'facets/' . $location->facetFile . '.ini';
			}
		}
	}

	return $configArray;
}

function formatHoldMessage($hold_message_data){
	global $interface;
	$interface->assign('hold_message_data', $hold_message_data);
	$hold_message = $interface->fetch('Record/hold-results.tpl');
	return $hold_message;
}
function formatRenewMessage($renew_message_data){
	global $interface;
	$interface->assign('renew_message_data', $renew_message_data);
	$renew_message = $interface->fetch('Record/renew-results.tpl');
	return $renew_message;
}