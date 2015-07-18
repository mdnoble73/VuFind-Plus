<?php
/**
 * Indexing information for what records should be included in a particular scope
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/18/2015
 * Time: 10:31 AM
 */

require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class RecordToInclude extends DB_DataObject{
	public $id;
	public $indexingProfileId;
	public $location;
	public $subLocation;
	public $includeHoldableOnly;
	public $includeItemsOnOrder;
	public $includeEContent;
	public $weight;

	static function getObjectStructure(){
		$indexingProfiles = array();
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()){
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of rule', 'default' => 0),
			'indexingProfileId' => array('property' => 'indexingProfileId', 'type' => 'enum', 'values' => $indexingProfiles, 'label' => 'Indexing Profile Id', 'description' => 'The Indexing Profile this map is associated with'),
			'location' => array('property'=>'location', 'type'=>'text', 'label'=>'Location', 'description'=>'A regular expression for location codes to include', 'maxLength' => '100', 'required' => true),
			'subLocation' => array('property'=>'subLocation', 'type'=>'text', 'label'=>'Sub Location', 'description'=>'A regular expression for sublocation codes to include', 'maxLength' => '100', 'required' => false),
			'includeHoldableOnly' => array('property'=>'includeHoldableOnly', 'type'=>'checkbox', 'label'=>'Include Holdable Only', 'description'=>'Whether or not non-holdable records are included'),
			'includeItemsOnOrder' => array('property'=>'includeItemsOnOrder', 'type'=>'checkbox', 'label'=>'Include Items On Order', 'description'=>'Whether or not order records are included'),
			'includeEContent' => array('property'=>'includeEContent', 'type'=>'checkbox', 'label'=>'Include Items On Order', 'description'=>'Whether or not order records are included'),
		);
		return $structure;
	}
}