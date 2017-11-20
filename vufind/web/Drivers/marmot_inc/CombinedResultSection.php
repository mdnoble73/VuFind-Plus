<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/17/2017
 * Time: 4:00 PM
 */

abstract class CombinedResultSection extends DB_DataObject{
	public $id;
	public $displayName;
	public $weight;
	public $source;
	public $numberOfResultsToShow;

	static function getObjectStructure(){
		$validResultSources = array('pika' => 'Pika Results');

		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this section'),
				'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order of the section', 'default' => 0),
				'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the section for display to the user'),
				'numberOfResultsToShow' => array('property'=>'numberOfResultsToShow', 'type'=>'integer', 'label'=>'Num Results', 'description'=>'The number of results to show in the box.', 'default' => '5'),
				'source' => array('property'=>'sortMode', 'type'=>'enum', 'label'=>'Source', 'values' => $validResultSources, 'description'=>'The source of results in the section.', 'default'=>'pika'),
		);
		return $structure;
	}
}