<?php
/**
 * Table Definition for NearbyBookStore.
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'Drivers/marmot_inc/BookStore.php';

class NearbyBookStore extends DB_DataObject
{
	public $__table = 'nearby_book_store';   // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $locationId;                      // int(11)
	public $storeId;                         // int(11)
	public $weight;                          // int(11)
	
	/* Static get */
	function staticGet($k,$v=NULL) {
		return DB_DataObject::staticGet('NearbyBookStore',$k,$v);
	}
	
	function keys() {
		return array('id');
	}

	function getObjectStructure(){		
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}
		
		$store = new BookStore();
		$store->orderBy('storeName');
		$store->find();
		$storeList = array();
		while ($store->fetch()){
			$storeList[$store->id] = $store->storeName;
		}
		
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'locationId' => array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location'),
			'storeId' => array('property'=>'storeId', 'type'=>'enum', 'values'=>$storeList, 'label'=>'Book Store', 'description'=>'The id of a book store'),
			'weight' => array('property'=>'weight', 'type'=>'text', 'label'=>'Weight', 'description'=>'The sort order of the book store'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
	
	static function getBookStores($locationId) {		
		$store = new BookStore();
		$store->query(
			"SELECT {$store->__table}.* FROM {$store->__table} " . 
			"LEFT JOIN nearby_book_store ON ({$store->__table}.id=nearby_book_store.storeId) " . 
			"WHERE locationId=$locationId"
		);
		$store->find();
		$list = array();
		while ($store->fetch()){
			$list[] = clone $store;
		}
		return $list;
	}
}