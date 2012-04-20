<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ReindexLogEntry extends DB_DataObject
{
	public $__table = 'reindex_log';   // table name
	public $id;
	public $startTime;
	public $endTime;
	private $_processes = null;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('ReindexLogEntry',$k,$v); }

	function keys() {
		return array('id');
	}

	function processes(){
		if (is_null($this->_processes)){
			$this->_processes = array();
			$reindexProcess = new ReindexProcessLogEntry();
			$reindexProcess->reindex_id = $this->id;
			$reindexProcess->order('processName');
			$reindexProcess->find();
			while ($reindexProcess->fetch()){
				$this->_processes[] = clone $reindexProcess;
			}
		}
		return $this->_processes;
	}
}
