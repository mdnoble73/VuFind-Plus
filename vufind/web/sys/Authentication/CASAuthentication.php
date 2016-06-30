<?php
require_once 'Authentication.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class CASAuthentication implements Authentication {
	private $username;
	private $password;
	private $driverName;
	/** @var  AccountProfile */
	private $accountProfile;
	private $catalogConnection;

	public function __construct($additionalInfo) {

	}

	public function authenticate($validatedViaSSO){
		global $configArray;
		global $library;
		require_once ROOT_DIR . '/CAS-1.3.4/CAS.php';

		if ($configArray['System']['debug']){
			phpCAS::setDebug();
			phpCAS::setVerbose(true);
		}

		phpCAS::client(CAS_VERSION_3_0, $library->casHost, (int)$library->casPort, $library->casContext);

		phpCAS::setNoCasServerValidation();

		try{
			$isValidated = phpCAS::forceAuthentication();
		}catch (CAS_AuthenticationException $e){
			global $logger;
			$logger->log("Error authenticating $e", PEAR_LOG_ERR);
			$isValidated = false;
		}

		return $isValidated;
	}

	/**
	 * @param $username       string Should be null for CAS
	 * @param $password       string Should be null for CAS
	 * @param $parentAccount  User|null
	 * @param $validatedViaSSO boolean
	 * @return bool|PEAR_Error|string return false if the user cannot authenticate, the barcode if they can, and an error if configuration is incorrect
	 */
	public function validateAccount($username, $password, $parentAccount, $validatedViaSSO) {
		if($this->username == '' || $this->password == ''){
			global $configArray;
			global $library;
			require_once ROOT_DIR . '/CAS-1.3.4/CAS.php';

			if ($configArray['System']['debug']){
				phpCAS::setDebug();
				phpCAS::setVerbose(true);
			}

			phpCAS::client(CAS_VERSION_3_0, $library->casHost, (int)$library->casPort, $library->casContext);

			phpCAS::setNoCasServerValidation();

			$isValidated = phpCAS::checkAuthentication();
			if ($isValidated){
				//We have a valid user within CAS.  Return the user id
				$userAttributes = phpCAS::getAttributes();
				//TODO: If we use other CAS systems we will need a configuration option to store which
				//attribute the id is in
				$userId = $userAttributes['flcid'];
				return $userId;
			}else{
				return false;
			}
		} else {
			return new PEAR_Error('Should not pass username and password to account validation for CAS');
		}
	}
}