<?php
require_once 'DB/DataObject.php';

class Analytics_Event extends DB_DataObject{
	public $__table = 'analytics_event';                        // table name
	public $sessionId;
	public $category;
	public $action;
	public $data;
	public $data2;
	public $data3;
	public $eventTime;

	//Dynamically created based on queries
	public $numEvents;
}