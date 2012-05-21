<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EContentMarcImport extends DB_DataObject 
{
	public $__table = 'econtent_marc_import';   // table name
	public $id;
	public $source;
	public $filename;
	public $supplementalFilename;
	public $accessType;
	public $dateStarted;
	public $dateFinished;
	public $status;
	public $recordsProcessed;
	
	/* Static get */
  function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentMarcImport',$k,$v); }
    
	function keys() {
	    return array('id');
 	}
}
