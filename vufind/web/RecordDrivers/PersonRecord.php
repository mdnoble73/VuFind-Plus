<?php
require_once 'RecordDrivers/IndexRecord.php';
require_once 'sys/Genealogy/Person.php';

/**
 * List Record Driver
 *
 * This class is designed to handle List records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class PersonRecord extends IndexRecord
{
	public function __construct($record)
	{
		// Call the parent's constructor...
		parent::__construct($record);
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult()
	{
		global $configArray;
		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$shortId = substr($id, 6);
		$interface->assign('summShortId', $shortId); //Trim the list prefix for the short id

		$person = new Person();
		$person->personId = $shortId;
		$person->find();
		if ($person->N > 0){
			$person->fetch();
			$person = Person::staticGet('personId', $shortId);
			$interface->assign('summPicture', $person->picture);
		}

		$name = '';
		if (isset($this->fields['firstName'])){
			$name = $this->fields['firstName'];
		}
		if (isset($this->fields['middleName'])){
			$name .= ' ' . $this->fields['middleName'];
		}
		$name .= ' ' . $this->fields['lastName'];
		$interface->assign('summTitle', trim($name));
		$interface->assign('birthDate', $person->formatPartialDate($person->birthDateDay, $person->birthDateMonth, $person->birthDateYear));
		$interface->assign('deathDate', $person->formatPartialDate($person->deathDateDay, $person->deathDateMonth, $person->deathDateYear));

		return 'RecordDrivers/Person/result.tpl';
	}

	function getBreadcrumb(){
		return $this->fields['firstName'] . ' ' . $this->fields['lastName'];
	}
}