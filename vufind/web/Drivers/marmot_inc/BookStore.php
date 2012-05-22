<?php
/**
 * Table Definition for BookStore.
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class BookStore extends DB_DataObject
{
	public $__table = 'book_store';   // table name
	public $id;                       // int(11)  not_null primary_key auto_increment
	public $storeName;                // varchar(100)
	public $link;                     // varchar(256)
	public $linkText;                 // varchar(100)
	public $image;                    // varchar(256)
	public $resultRegEx;              // varchar(100)
	
	/* Static get */
	function staticGet($k,$v=NULL) {
		return DB_DataObject::staticGet('BookStore',$k,$v);
	}
	
	function keys() {
		return array('id');
	}

	function getObjectStructure(){		
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the book store within the database'),
			'storeName' => array('property'=>'storeName', 'type'=>'text', 'label'=>'Store Name', 'description'=>'The name of a book store'),
			'link' => array('property'=>'link', 'type'=>'text', 'label'=>'Link', 'description'=>'The link to search'),
			'linkText' => array('property'=>'linkText', 'type'=>'text', 'label'=>'Link Text', 'description'=>'The search link text to display'),
			'image' => array('property'=>'image', 'type'=>'image', 'label'=>'Image', 'description'=>'The image/icon for the book store'),
			'resultRegEx' => array('property'=>'resultRegEx', 'type'=>'text', 'label'=>'Result RegEx', 'description'=>'The RegEx to check search result'),
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}