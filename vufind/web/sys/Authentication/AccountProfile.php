<?php
/**
 * Authentication Profile information to configure how users should be authenticated
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/20/2015
 * Time: 4:48 PM
 */

class AccountProfile extends DB_DataObject {
	public $__table = 'account_profiles';    // table name

	public $id;
	public $name;
	public $driver;
	public $loginConfiguration;
	public $authenticationMethod;

	function getObjectStructure() {
		$translationMapStructure = TranslationMap::getObjectStructure();
		unset($translationMapStructure['indexingProfileId']);

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'A name for this indexing profile', 'required' => true),
			'driver' => array('property' => 'driver', 'type' => 'text', 'label' => 'Driver', 'maxLength' => 50, 'description' => 'The name of the driver to use for authentication', 'required' => true),
			'loginConfiguration' => array('property' => 'loginConfiguration', 'type' => 'enum', 'label' => 'Login Configuration', 'values' => array('barcode_pin' => 'Barcode and Pin','name_barcode' => 'Name and Barcode'), 'description' => 'How to configure the prompts for this authentication profile', 'required' => true),
			'authenticationMethod' => array('property' => 'authenticationMethod', 'type' => 'enum', 'label' => 'Authentication Method', 'values' => array('ils' => 'ILS','sip2' => 'SIP 2','db' => 'Database','ldap' => 'LDAP') , 'description' => 'The method of authentication to use', 'required' => true),
		);
		return $structure;
	}
}