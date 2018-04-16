<?php
/**
 * Provides information for mapping fixed bib fields and variable item fields to MARC records when using the Sierra Export.
 *
 * User: mnoble
 * Date: 4/16/2018
 * Time: 12:17 PM
 */

class SierraExportFieldMapping extends DB_DataObject{
	public $__table = 'sierra_export_field_mapping';    // table name
	public $id;
	public $indexingProfileId;
	public $bcode3DestinationField;
	public $bcode3DestinationSubfield;


	function getObjectStructure(){
		$indexingProfiles = array();
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()){
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id within the database'),
				'indexingProfileId' => array('property' => 'indexingProfileId', 'type' => 'enum', 'values' => $indexingProfiles, 'label' => 'Indexing Profile Id', 'description' => 'The Indexing Profile this map is associated with'),
				'bcode3DestinationField' => array('property' => 'bcode3DestinationField', 'type' => 'text', 'label' => 'BCode3 Destination Field', 'maxLength' => 3, 'description' => 'The MARC field where BCode3 should be stored'),
				'bcode3DestinationSubfield' => array('property' => 'bcode3DestinationSubfield', 'type' => 'text', 'label' => 'BCode3 Destination Subfield', 'maxLength' => 1, 'description' => 'Subfield for where BCode3 should be stored'),
		);
		return $structure;
	}
}