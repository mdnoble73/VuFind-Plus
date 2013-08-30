<?php
/**
 * Allows staff to return titles and checkout titles while the ILS is offline
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/26/13
 * Time: 10:27 AM
 */

class Circa_OfflineCirculation extends Action{
	function launch()
	{
		global $interface;

		if (isset($_POST['submit'])){
			require_once ROOT_DIR . '/sys/OfflineCirculationEntry.php';
			//Store information into the database
			$login = $_REQUEST['login'];
			$interface->assign('lastLogin', $login);
			$password1 = $_REQUEST['password1'];
			$interface->assign('lastPassword1', $password1);
			$initials = $_REQUEST['initials'];
			$interface->assign('lastInitials', $initials);
			$password2 = $_REQUEST['password2'];
			$interface->assign('lastPassword2', $password2);

			//$barcodesToCheckIn = $_REQUEST['barcodesToCheckIn'];
			$patronBarcode = $_REQUEST['patronBarcode'];
			$barcodesToCheckOut = $_REQUEST['barcodesToCheckOut'];

			//First store any titles that are being checked in
			/*if (strlen(trim($barcodesToCheckIn)) > 0){
				$barcodesToCheckIn = preg_split('/[\\s\\r\\n]+/', $barcodesToCheckIn);
				foreach ($barcodesToCheckIn as $barcode){
					$offlineCirculationEntry = new OfflineCirculationEntry();
					$offlineCirculationEntry->timeEntered = time();
					$offlineCirculationEntry->itemBarcode = $barcode;
					$offlineCirculationEntry->login = $login;
					$offlineCirculationEntry->loginPassword = $password1;
					$offlineCirculationEntry->initials = $initials;
					$offlineCirculationEntry->initialsPassword = $password2;
					$offlineCirculationEntry->type = 'Check In';
					$offlineCirculationEntry->status = 'Not Processed';
					$offlineCirculationEntry->insert();
				}
			}*/
			if (strlen(trim($barcodesToCheckOut)) > 0 && strlen($patronBarcode >0)){
				$userObj = new User();
				$patronId = null;
				$userObj->cat_password = $patronBarcode;
				if ($userObj->find()){
					$userObj->fetch();
					$patronId = $userObj->id;
				}
				$barcodesToCheckOut = preg_split('/[\\s\\r\\n]+/', $barcodesToCheckOut);
				foreach ($barcodesToCheckOut as $barcode){
					$offlineCirculationEntry = new OfflineCirculationEntry();
					$offlineCirculationEntry->timeEntered = time();
					$offlineCirculationEntry->itemBarcode = $barcode;
					$offlineCirculationEntry->login = $login;
					$offlineCirculationEntry->loginPassword = $password1;
					$offlineCirculationEntry->initials = $initials;
					$offlineCirculationEntry->initialsPassword = $password2;
					$offlineCirculationEntry->patronBarcode = $patronBarcode;
					$offlineCirculationEntry->patronId = $patronId;
					$offlineCirculationEntry->type = 'Check Out';
					$offlineCirculationEntry->status = 'Not Processed';
					$offlineCirculationEntry->insert();
				}
			}

		}

		//Get view & load template
		$interface->setTemplate('offlineCirculation.tpl');
		$interface->display('layout.tpl', 'Circa');
	}
}