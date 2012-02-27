<?php
/**
 * Table Definition for Person
 */
require_once 'sys/SolrDataObject.php';
require_once 'sys/Genealogy/Marriage.php';
require_once 'sys/Genealogy/Obituary.php';

class Person extends SolrDataObject
{
	public $__table = 'person';    // table name
	public $personId;
	public $firstName;
	public $middleName;
	public $lastName;
	public $maidenName;
	public $otherName;
	public $nickName;
	public $veteranOf;

	//Age information
	public $birthDate;
	public $birthDateDay;
	public $birthDateMonth;
	public $birthDateYear;
	public $deathDate;
	public $deathDateDay;
	public $deathDateMonth;
	public $deathDateYear;
	public $ageAtDeath;

	//Burial information
	public $cemeteryName;
	public $cemeteryLocation;
	public $addition;
	public $block;
	public $lot;
	public $grave;
	public $tombstoneInscription;

	public $mortuaryName;
	public $picture;
	public $comments;
	
	//Revision history information 
	public $addedBy;
	public $dateAdded;
	public $modifiedBy;
	public $lastModified;
	public $importedFrom;
	public $privateComments;

	private $obituaries = null;
	private $marriages = null;

	private $data;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Person',$k,$v); }

	function keys() {
		return array('personId');
	}

	function cores(){
		return array('genealogy');
	}
	function getConfigSection(){
		return 'Genealogy';
	}
	function solrId(){
		return 'person' . $this->personId;
	}
	function recordtype(){
		return 'person';
	}
	function displayName(){
		return $this->firstName . ' ' . $this->lastName;
	}
	function title(){
		return $this->firstName . ' ' . $this->lastName . ' ' . $this->middleName . ' ' . $this->otherName. ' ' . $this->maidenName;
	}
	function keywords(){
		$keywords = $this->firstName . ' ' .
		$this->lastName . ' ' .
		$this->middleName  . ' ' .
		$this->otherName . ' ' .
		$this->nickName . ' ' .
		$this->maidenName . ' ';
		$keywords .= $this->cemeteryName . ' ' .
		$this->cemeteryLocation . ' ' .
		$this->mortuaryName . ' ';
		$keywords .= $this->comments . ' ';
		$keywords .= $this->tombstoneInscription . ' ';
		$keywords .= $this->veteranOf . ' ';
		$keywords .= join(' ', $this->marriageComments()) . ' ';
		$keywords .= join(' ', $this->obituaryText()) . ' ';
		return $keywords;
	}
	function birthYear(){
		return $this->birthDateYear;
	}
	function deathYear(){
		return $this->deathDateYear;
	}
	function spouseName(){
		$return = array();
		//Make sure that marriages are loaded
		$marriages = $this->__get('marriages');
		foreach ($marriages as $marriage){
			$return[] = $marriage->spouseName;
		}
		return $return;
	}
	function marriageDate(){
		$return = array();
		//Make sure that marriages are loaded
		$marriages = $this->__get('marriages');
		foreach ($marriages as $marriage){
			$dateParts = date_parse($marriage->marriageDate);
			if ($dateParts['year'] != false && $dateParts['month'] != false && $dateParts['day'] != false){
				$time = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'] . 'T00:00:00Z';
				$return[] =  $time;
			}
		}
		return $return;
	}
	function marriageComments(){
		$return = array();
		//Make sure that marriages are loaded
		$marriages = $this->__get('marriages');
		foreach ($marriages as $marriage){
			$return[] = $marriage->comments;
		}
		return $return;
	}
	function obituaryDate(){
		$return = array();
		//Make sure that obituaries are loaded
		$obituaries = $this->__get('obituaries');
		foreach ($obituaries as $obit){
			$dateParts = date_parse($obit->date);
			if ($dateParts['year'] != false && $dateParts['month'] != false && $dateParts['day'] != false){
				$time = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'] . 'T00:00:00Z';
				$return[] =  $time;
			}
		}
		return $return;
	}
	function obituarySource(){
		$return = array();
		//Make sure that obituaries are loaded
		$obituaries = $this->__get('obituaries');
		foreach ($obituaries as $obit){
			$return[] = $obit->source;
		}
		return $return;
	}
	function obituaryText(){
		$return = array();
		//Make sure that obituaries are loaded
		$obituaries = $this->__get('obituaries');
		foreach ($obituaries as $obit){
			$return[] = $obit->contents;
		}
		return $return;
	}

	function getObjectStructure(){
		$structure = array(
		array('property'=>'id', 'type'=>'method', 'methodName'=>'solrId', 'storeDb' => false, 'storeSolr' => true, 'hideInLists'=>true),
		array('property'=>'recordtype', 'type'=>'method', 'methodName'=>'recordtype', 'storeDb' => false, 'storeSolr' => true, 'hideInLists'=>true),
		array('property'=>'personId', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the person in the database', 'storeDb' => true, 'storeSolr' => false),
		array('property'=>'firstName', 'type'=>'text', 'maxLength'=>100, 'label'=>'First Name', 'description'=>'The person&apos;s First Name', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'lastName', 'type'=>'text', 'maxLength'=>100, 'label'=>'Last Name', 'description'=>'The person&apos;s Last Name', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'middleName', 'type'=>'text', 'maxLength'=>100, 'label'=>'Middle Name', 'description'=>'The person&apos;s Middle Name', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'maidenName', 'type'=>'text', 'maxLength'=>100, 'label'=>'Maiden Name', 'description'=>'The person&apos;s Maiden Name', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'otherName', 'type'=>'text', 'maxLength'=>100, 'label'=>'Other Name', 'description'=>'Another name the person went by', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'nickName', 'type'=>'text', 'maxLength'=>100, 'label'=>'Nick Name', 'description'=>'The person&apos;s Nick Name', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'veteranOf', 'type'=>'crSeparated', 'rows'=>2, 'cols'=>80, 'label'=>'Veteran Of', 'description'=>'A list of war(s) that the person served in.', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'birthDate', 'type'=>'partialDate', 'label'=>'Birth Date', 'description'=>'The date the person was born.', 'storeDb' => true, 'storeSolr' => true, 'propNameMonth'=>'birthDateMonth', 'propNameDay'=>'birthDateDay', 'propNameYear'=>'birthDateYear'),
		array('property'=>'deathDate', 'type'=>'partialDate', 'label'=>'Death Date', 'description'=>'The date the person died.', 'storeDb' => true, 'storeSolr' => true, 'propNameMonth'=>'deathDateMonth', 'propNameDay'=>'deathDateDay', 'propNameYear'=>'deathDateYear'),
		array('property'=>'ageAtDeath', 'type'=>'text', 'maxLength'=>100, 'label'=>'Age At Death', 'description'=>'The age (can be approximate) the person was when they died if exact birth or death dates are not known.', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'cemeteryName', 'type'=>'text', 'maxLength'=>255, 'label'=>'Cemetery', 'description'=>'The cemetery where the person is buried.', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'cemeteryLocation', 'type'=>'text', 'maxLength'=>255, 'label'=>'Cemetery Location', 'description'=>'The location of the cemetery.', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'addition', 'type'=>'text', 'maxLength'=>100, 'label'=>'Cemetery Addition', 'description'=>'The addition within the cemetery where the person is buried.', 'storeDb' => true, 'storeSolr' => false),
		array('property'=>'block', 'type'=>'text', 'maxLength'=>255, 'label'=>'Cemetery Block', 'description'=>'The block within the cemetery where the person is buried.', 'storeDb' => true, 'storeSolr' => false),
		array('property'=>'lot', 'type'=>'integer', 'maxLength'=>6, 'size'=>6, 'label'=>'Cemetery Lot', 'description'=>'The lot of the cemetery where the person is buried.', 'storeDb' => true, 'storeSolr' => false),
		array('property'=>'grave', 'type'=>'integer', 'maxLength'=>6, 'size'=>6, 'label'=>'Cemetery Grave Number', 'description'=>'The grave number within the cemetery where the person is buried.', 'storeDb' => true, 'storeSolr' => false),
		array('property'=>'tombstoneInscription', 'type'=>'textarea', 'rows'=>2, 'cols'=>80, 'label'=>'Tombstone Inscription', 'description'=>'The inscription on the tombstone.', 'storeDb' => true, 'storeSolr' => false),
		
		array('property'=>'mortuaryName', 'type'=>'text', 'maxLength'=>255, 'label'=>'Mortuary', 'description'=>'The mortuary who performed the burial.', 'storeDb' => true, 'storeSolr' => true),
		array('property'=>'comments', 'type'=>'textarea', 'rows'=>2, 'cols'=>80, 'label'=>'Comments', 'description'=>'Comments for the user.  Will be displayed on the record and can be searched.', 'storeDb' => true, 'storeSolr' => true, 'hideInLists'=>true),
		array('property'=>'picture', 'type'=>'image', 'thumbWidth' => 65, 'mediumWidth'=>190, 'label'=>'Picture', 'description'=>'A picture of the person.', 'storeDb' => true, 'storeSolr' => false, 'hideInLists'=>true),
		array('property'=>'privateComments', 'type'=>'textarea', 'rows'=>2, 'cols'=>80, 'label'=>'Private Comments', 'description'=>'Internal Comments for a person that is not displayed in the record and is not searchable.', 'storeDb' => true, 'storeSolr' => false, 'hideInLists'=>true),
		
		/* Properties related to data entry of the person */
		array('property'=>'addedBy', 'type'=>'hidden', 'label'=>'Added By', 'description'=>'The id of the user who added the person', 'storeDb' => true, 'storeSolr' => false),
		array('property'=>'modifiedBy', 'type'=>'hidden', 'label'=>'Modified By', 'description'=>'The id of the user who modified the person', 'storeDb' => true, 'storeSolr' => false),
		array('property' => 'dateAdded', 'type' => 'hidden', 'label' => 'Date Added', 'description' => 'The Date the person was added.', 'required'=> false, 'storeDb' => true, 'storeSolr' => false),
		array('property' => 'dateAdded', 'type' => 'hidden', 'label' => 'Date Modified', 'description' => 'The Date the person was last modified.', 'required'=> false, 'storeDb' => true, 'storeSolr' => false),
		
		/* proprties to store in solr */
		array('property'=>'title', 'type'=>'method', 'description'=>'The full name for the person for Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'keywords', 'type'=>'method', 'description'=>'Keywords for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'birthYear', 'type'=>'method', 'description'=>'The year the person was born for faceting within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'deathYear', 'type'=>'method', 'description'=>'The year the person was died for faceting within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'spouseName', 'type'=>'method', 'description'=>'Spouse Name for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'marriageDate', 'type'=>'method', 'description'=>'Marriage Date for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'marriageComments', 'type'=>'method', 'description'=>'Marriage Comments for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'obituaryDate', 'type'=>'method', 'description'=>'Spouse Name for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'obituarySource', 'type'=>'method', 'description'=>'Marriage Date for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),
		array('property'=>'obituaryText', 'type'=>'method', 'description'=>'Marriage Comments for searching within Solr', 'storeDb' => false, 'storeSolr' => true, 'hideInLists' => true),

		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}

	function __get($name){
		global $timer;
		if ($name == 'displayName'){
			return $this->firstName . ' ' . $this->lastName;
		}else if ($name == 'marriages') {
			if (is_null($this->marriages)){
				$this->marriages = array();
				if ($this->personId > 0){
					//Load roles for the user from the user
					$marriage = new Marriage();
					$marriage->personId = $this->personId;
					$marriage->orderBy('marriageDateYear ASC');
					$marriage->find();
					while ($marriage->fetch()){
						$this->marriages[$marriage->marriageId] = clone($marriage);
					}
				}
				$timer->logTime("Loaded marriages");
				return $this->marriages;
			}else{
				return $this->marriages;
			}
		}else if ($name == 'obituaries') {
			if (is_null($this->obituaries)){
				$this->obituaries = array();
				if ($this->personId > 0){
					//Load roles for the user from the user
					$obit = new Obituary();
					$obit->personId = $this->personId;
					$obit->orderBy('source ASC');
					$obit->find();
					while ($obit->fetch()){
						$this->obituaries[$obit->obituaryId] = clone($obit);
					}
				}
				$timer->logTime("Loaded obituaries");
				return $this->obituaries;
			}else{
				return $this->obituaries;
			}
		}else{
			return $this->data[$name];
		}
	}

	function __set($name, $value){
		if ($name == 'marriages'){
			$this->marriages = $value;
			//Update the database, first remove existing values
			$this->saveMarriages();
		}elseif ($name == 'obituaries'){
			$this->obituaries = $value;
			//Update the database, first remove existing values
			$this->saveObituaries();
		}else{
			$this->data[$name] = $value;
		}
	}

	function deleteMarriages(){
		if (isset($this->personId)){
			$marriage = new Marriage();
			$marriage->query("DELETE FROM marriage WHERE personId = {$this->personId}");
		}
	}

	function deleteObituaries(){
		if (isset($this->personId)){
			$obit = new Obituary();
			$obit->query("DELETE FROM obituary WHERE personId = {$this->personId}");
		}
	}

	function delete(){
		$this->deleteMarriages();
		$this->deleteObituaries();
		parent::delete();
	}

	function saveMarriages(){
		if (isset($this->personId)){
			$marriage = new Marriage();
			$marriage->query("DELETE FROM marriage WHERE personId = {$this->personId}");
			if (is_array($this->marriages)){
				foreach ($this->marriages as $marriageData){
					$marriageData->personId = $this->personId;
					$marriageData->insert();
				}
			}
		}
	}

	function saveObituaries(){
		if (isset($this->personId)){
			$obit = new Obituary();
			$obit->query("DELETE FROM obituary WHERE personId = {$this->personId}");
			if (is_array($this->obituaries)){
				foreach ($this->obituaries as $obitData){
					$obitData->personId = $this->personId;
					$obitData->insert();
				}
			}
		}
	}

	function insert(){
		//Set the dateAdded and who added the record
		global $user;
		$this->dateAdded = time();
		$this->addedBy = $user->id;
		$this->modifiedBy = $user->id;
		$this->lastModified = time();
		parent::insert();
		$this->saveMarriages();
		$this->saveObituaries();
	}

	function update(){
		$this->modifiedBy = $user->id;
		$this->lastModified = time();
		parent::update();
		$this->saveMarriages();
		$this->saveObituaries();
	}

	function formatPartialDate($day, $month, $year){
		$months = array(
		1=>'January',
		2=>'February',
		3=>'March',
		4=>'April',
		5=>'May',
		6=>'June',
		7=>'July',
		8=>'August',
		9=>'September',
		10=>'October',
		11=>'November',
		12=>'December'
		);
		$formattedDate = '';
		if ($month > 0){
			$formattedDate = $months[$month];
		}
		if ($day > 0){
			if (strlen($formattedDate) > 0) $formattedDate .= ' ';
			$formattedDate .= $day;

		}
		if ($year > 0){
			if (strlen($formattedDate) > 0 && $day > 0) $formattedDate .= ', ';
			$formattedDate .= ' ' . $year;
		}
		return $formattedDate;
	}
}