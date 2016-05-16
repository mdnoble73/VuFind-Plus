<?php

/**
 * Allows downloading the large image for an Object after checking permissions
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/16/2016
 * Time: 10:47 AM
 */
require_once ROOT_DIR . '/services/Archive/Object.php';
class DownloadLC extends Archive_Object{
	function launch(){
		global $interface;
		global $user;
		$this->loadArchiveObjectData();
		$anonymousLcDownload = $interface->getVariable('anonymousLcDownload');
		$verifiedLcDownload = $interface->getVariable('verifiedLcDownload');

		if ($anonymousLcDownload || ($user && $verifiedLcDownload)){

		}else{
			PEAR_Singleton::raiseError('Sorry, You do not have permission to download this image.');
		}
	}
}