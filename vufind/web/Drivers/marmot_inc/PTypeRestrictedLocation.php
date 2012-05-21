<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class PTypeRestrictedLocation extends DB_DataObject 
{
	public $__table = 'ptype_restricted_locations';   // table name
	public $locationId;				//int(11)
	public $millenniumCode;			//varchar(5)
	public $holdingDisplay;         //varchar(30)`
  public $allowablePtypes;	//tinyint(4)
	
	/* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('PTypeRestrictedLocation',$k,$v); }
    
	function keys() {
	    return array('locationId');
 	}
 }