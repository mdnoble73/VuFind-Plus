<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EContentAttachmentLogEntry extends DB_DataObject
{
	public $__table = 'econtent_attach';   // table name
	public $id;
	public $sourcePath;
	public $dateStarted;
	public $dateFinished;
	public $status;
	public $recordsProcessed;
	public $numErrors;
	public $notes;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentAttachmentLogEntry',$k,$v); }

	function keys() {
		return array('id');
	}
}
