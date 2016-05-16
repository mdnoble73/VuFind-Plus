<?php

/**
 * Allows downloading the original object after checking permissions
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/16/2016
 * Time: 10:47 AM
 */
require_once ROOT_DIR . '/services/Archive/Object.php';
class DownloadOriginal {
	function launch(){
		global $interface;
		global $user;
		$this->loadArchiveObjectData();
		$anonymousLcDownload = $interface->getVariable('anonymousLcDownload');
		$verifiedLcDownload = $interface->getVariable('verifiedLcDownload');


	}
}