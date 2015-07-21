<?php
/**
 * Integration with
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/20/2015
 * Time: 2:17 PM
 */

require_once ROOT_DIR . '/Drivers/SIP2Driver.php';
class LibrarySolution extends SIP2Driver {

	/**
	 * Loads items information as quickly as possible (no direct calls to the ILS)
	 *
	 * return is an array of items with the following information:
	 *  callnumber
	 *  available
	 *  holdable
	 *  lastStatusCheck (time)
	 *
	 * @param $id
	 * @param $scopingEnabled
	 * @return mixed
	 */
	public function getItemsFast($id, $scopingEnabled) {
		// TODO: Implement getItemsFast() method.
	}

	public function getStatus($id) {
		// TODO: Implement getStatus() method.
	}

	public function getStatuses($ids) {
		// TODO: Implement getStatuses() method.
	}

	public function getHolding($id) {
		// TODO: Implement getHolding() method.
	}

	public function getPurchaseHistory($id) {
		// TODO: Implement getPurchaseHistory() method.
	}

	public function getMyProfile($patron, $forceReload = false) {
		// TODO: Implement getMyProfile() method.
	}

	public function patronLogin($username, $password) {
		if ($this->initSipConnection('tlcweb01.mnps.org', '6001')){

		}else{
			PEAR_Singleton::raiseError("Could not connect to Library.Solution SIP2 server");
		}
	}

	public function hasNativeReadingHistory() {
		// TODO: Implement hasNativeReadingHistory() method.
	}

	public function getNumHolds($id) {
		// TODO: Implement getNumHolds() method.
	}


}