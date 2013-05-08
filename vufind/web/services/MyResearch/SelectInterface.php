<?php
/**
 * Allow the user to select an interface to use to access the site.
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/8/13
 * Time: 2:32 PM
 */

class MyResearch_SelectInterface extends Action{
	function launch(){
		global $interface;
		global $user;
		global $logger;

		$libraries = array();
		$library = new Library();
		$library->orderBy('displayName');
		$library->find();
		while ($library->fetch()){
			$libraries[$library->libraryId] = array(
				'id' => $library->libraryId,
				'displayName' => $library->displayName,
				'subdomain' => $library->subdomain,
			);
		}
		$interface->assign('libraries', $libraries);

		if (isset($_REQUEST['library'])){
			$selectedLibraryId = $_REQUEST['library'];
			$logger->log("Selected library $selectedLibraryId", PEAR_LOG_DEBUG);
			$selectedLibrary = $libraries[$selectedLibraryId];
			global $configArray;
			$baseUrl = $configArray['Site']['url'];
			$urlPortions = explode('://', $baseUrl);
			$logger->log("Redirecting from $baseUrl " . print_r($urlPortions, true), PEAR_LOG_DEBUG);
			//Get rid of extra portions of the url
			$urlPortions[1] = str_replace('opac2.', '', $urlPortions[1]);
			$urlPortions[1] = str_replace('opac.', '', $urlPortions[1]);
			$baseUrl = $urlPortions[0] . '://' . $selectedLibrary['subdomain'] . '.' . $urlPortions[1];

			$logger->log("Redirecting to $baseUrl", PEAR_LOG_DEBUG);
			header('Location:' . $baseUrl);
			die();
		}

		//Build the actual view
		$interface->setTemplate('selectInterface.tpl');
		$interface->setPageTitle('Select your preferred interface');

		// Display Page
		$interface->display('layout.tpl');
	}
}