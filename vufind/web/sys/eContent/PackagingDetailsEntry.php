<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class PackagingDetailsEntry extends DB_DataObject
{
	public $__table = 'acs_packaging_log';   // table name
	public $id;
	public $filename;
	public $distributorId;
	public $copies;
	public $previousAcsId;
	public $created;
	public $lastUpdate;
	public $packagingStartTime;
	public $packagingEndTime;
	public $acsError;
	public $acsId;
	public $status;
	
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('PackagingDetailsEntry',$k,$v); }

	function keys() {
		return array('id');
	}
}
