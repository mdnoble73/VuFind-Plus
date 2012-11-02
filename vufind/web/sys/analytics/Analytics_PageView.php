<?php
require_once 'DB/DataObject.php';

class Analytics_PageView extends DB_DataObject
{
	public $__table = 'analytics_page_view';                        // table name
	public $id;
	public $sessionId;
	public $pageStartTime;
	public $pageEndTime;
	public $language;
	public $module;
	public $action;
	public $method;
	public $objectId;
	public $fullUrl;

}