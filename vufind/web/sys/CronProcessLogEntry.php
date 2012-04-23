<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class CronProcessLogEntry extends DB_DataObject
{
	public $__table = 'cron_process_log';   // table name
	public $id;
	public $cronId;
	public $processName;
	public $startTime;
	public $lastUpdate;
	public $endTime;
	public $numErrors;
	public $numUpdated;
	public $notes;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('CronProcessLogEntry',$k,$v); }

	function keys() {
		return array('id');
	}
	
}
