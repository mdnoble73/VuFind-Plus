<?php
/**
 * Integration with Sierra.  Mostly inherits from Millennium since the systems are similar
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/28/13
 * Time: 9:58 AM
 */
require_once ROOT_DIR . '/Drivers/Millennium.php';
class Sierra extends MillenniumDriver{
	private $dbConnection = false;

	public function getDbConnection(){
		if (!$this->dbConnection){
			global $configArray;
			$this->dbConnection = pg_connect($configArray['Catalog']['sierra_conn_php']);
		}
	}

	public function getStatus($id){
		global $timer;

		if (isset($this->statuses[$id])){
			return $this->statuses[$id];
		}
		require_once ROOT_DIR . '/Drivers/marmot_inc/SierraStatusLoader.php';
		$millenniumStatusLoader = new SierraStatusLoader($this);
		//Load circulation status information so we can use it later on to
		//determine what is holdable and what is not.
		self::loadCircStatusInfo();
		self::loadLoanRules();
		$timer->logTime('loadCircStatusInfo, loadLoanRules');

		$this->statuses[$id] = $millenniumStatusLoader->getStatus($id);

		return $this->statuses[$id];
	}

	public function __destruct(){
		if ($this->dbConnection){
			pg_close($this->dbConnection);
		}
	}
} 