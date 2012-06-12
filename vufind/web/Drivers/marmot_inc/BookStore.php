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
	public $showByDefault;                  // tinyint (0 or 1)
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
			'storeName' => array('property'=>'storeName', 'type'=>'text', 'label'=>'Store Name', 'maxLength'=>'100', 'size'=>'80', 'description'=>'The name of a book store'),
			'link' => array('property'=>'link', 'type'=>'text', 'label'=>'Link', 'maxLength'=>'256', 'size'=>'80', 'description'=>'The link to search'),
			'linkText' => array('property'=>'linkText', 'type'=>'text', 'label'=>'Link Text', 'maxLength'=>'100', 'size'=>'80', 'description'=>'The search link text to display'),
			'image' => array('property'=>'image', 'type'=>'image', 'label'=>'Image', 'description'=>'The image/icon for the book store', 'hideInLists'=>true),
			'resultRegEx' => array('property'=>'resultRegEx', 'type'=>'text', 'label'=>'No Results RegEx', 'maxLength'=>'100', 'size'=>'80', 'description'=>'The RegEx to determine if the title does not exist at the store.'),
			'showByDefault' => array('property'=>'showByDefault', 'type'=>'checkbox', 'label'=>'Use by default', 'description'=>'Whether or not to use the bookstore by default if bookstores are not setup for a particular library.')
		);
		foreach ($structure as $fieldName => $field){
			$field['propertyOld'] = $field['property'] . 'Old';
			$structure[$fieldName] = $field;
		}
		return $structure;
	}
}