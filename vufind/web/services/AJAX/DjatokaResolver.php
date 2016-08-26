<?php

/**
 * Resove
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/26/2016
 * Time: 2:15 PM
 */

require_once ROOT_DIR . '/Action.php';
class DjatokaResolver extends Action{

	function launch() {
		//Pass the request to the Islandora server for processing

		global $configArray;
		$requestUrl = $configArray['Islandora']['repositoryUrl'] . '/adore-djatoka/resolver?' . $_SERVER['QUERY_STRING'];

		$response = file_get_contents($requestUrl);

		echo($response);
	}
}