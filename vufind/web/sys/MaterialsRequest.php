<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class MaterialsRequest extends DB_DataObject 
{
	public $__table = 'materials_request';   // table name
	public $id;
	public $title;
	public $author;
	public $format;
	public $ageLevel;
	public $isbn_upc;
	public $oclcNumber;
	public $publisher;
	public $publicationYear;
	public $articleInfo;
	public $abridged;
	public $about;
	public $comments;
	public $status;
	public $dateCreated;
	public $createdBy;
	public $dateUpdated;
	public $emailSent;
	public $holdsCreated;
	
	/* Static get */
  function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('MaterialsRequest',$k,$v); }
    
	function keys() {
	    return array('id');
 	}
}
