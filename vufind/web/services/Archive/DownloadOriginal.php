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
class DownloadOriginal extends Archive_Object{
	function launch(){
		global $interface;
		$this->loadArchiveObjectData();
		$anonymousMasterDownload = $interface->getVariable('anonymousMasterDownload');
		$verifiedMasterDownload = $interface->getVariable('verifiedMasterDownload');

		if ($anonymousMasterDownload || (UserAccount::isLoggedIn() && $verifiedMasterDownload)){
			$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
			header("Cache-Control: maxage=".$expires);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
			$pid = $this->pid;
			$pid = str_replace(':', '_', $pid);
			header('Content-Disposition: attachment; filename=' . $pid . '_original' . $this->recordDriver->getExtension($this->archiveObject->getDatastream('OBJ')->mimetype));
			header('Content-type: ' . $this->archiveObject->getDatastream('OBJ')->mimetype);
			$masterDataStream = $this->archiveObject->getDatastream('OBJ');
			echo($masterDataStream->content);
			exit();
		}else{
			PEAR_Singleton::raiseError('Sorry, You do not have permission to download this image.');
		}
	}
}