<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ReindexProcessLogEntry extends DB_DataObject
{
	public $__table = 'reindex_process_log';   // table name
	public $id;
	public $reindex_id;
	public $processName;
	public $recordsProcessed;
	public $eContentRecordsProcessed;
	public $resourcesProcessed;
	public $numErrors;
	public $numAdded;
	public $numUpdated;
	public $numDeleted;
	public $numSkipped;
	public $notes;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ReindexProcessLogEntry',$k,$v); }

	function keys() {
		return array('id');
	}
	
}
