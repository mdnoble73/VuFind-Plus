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
	public $season;
	public $magazineTitle;
	public $magazineDate;
	public $magazineVolume;
	public $magazinePageNumbers;
	public $author;
	public $format;
	public $subFormat;
	public $ageLevel;
	public $bookType;
	public $isbn;
	public $upc;
	public $issn;
	public $oclcNumber;
	public $publisher;
	public $publicationYear;
	public $abridged;
	public $about;
	public $comments;
	public $status;
	public $phone;
	public $email;
	public $dateCreated;
	public $createdBy;
	public $dateUpdated;
	public $emailSent;
	public $holdsCreated;
	public $placeHoldWhenAvailable;
	public $holdPickupLocation;
	public $bookmobileStop;
	
	/* Static get */
  function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('MaterialsRequest',$k,$v); }
    
	function keys() {
	    return array('id');
 	}
}
