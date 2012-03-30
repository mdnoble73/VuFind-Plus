<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class subnet extends DB_DataObject
{
	public $__table = 'ip_lookup';    // table name
	public $id;                      //int(25)
	public $locationid;                    //int(5)
	public $location;             //varchar(255)
	public $ip;					//varchar(255)
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ip_lookup',$k,$v); }

	function keys() {
		return array('id', 'locationid', 'ip');
	}
}