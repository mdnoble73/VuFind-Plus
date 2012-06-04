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
		$library = new Library();
		$library->orderBy('displayName');
		$library->find();
		$libraryList = array('-1'=>'Default');
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
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
			'libraryId' => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library'),
			'storeId' => array('property'=>'storeId', 'type'=>'enum', 'values'=>$storeList, 'label'=>'Book Store', 'description'=>'The id of a book store'),
			'weight' => array('property'=>'weight', 'type'=>'text', 'label'=>'Weight', 'description'=>'The sort order of the book store'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
	
	static function getBookStores($libraryId) {		
		$store = new BookStore();
		if ($libraryId == -1){
			$store->query(
				"SELECT {$store->__table}.* FROM {$store->__table} "
			);
		}else{
			$store->query(
				"SELECT {$store->__table}.* FROM {$store->__table} " . 
				"INNER JOIN nearby_book_store ON ({$store->__table}.id=nearby_book_store.storeId) " . 
				"WHERE libraryId=$libraryId " .
				"ORDER BY weight"
			);
		}
		$list = array();
		while ($store->fetch()){
			$list[] = clone $store;
		}
		if (count($list) == 0){
			$list = NearbyBookStore::getDefaultBookStores();
		}
		return $list;
	}
	
	static function getDefaultBookStores(){
		$store = new BookStore();
		$store->showByDefault = 1;
		$store->find();
		$list = array();
		while ($store->fetch()){
			$list[] = clone $store;
		}
		return $list;
	}
}