<?php
require_once 'DB/DataObject.php';

class Analytics_Search extends DB_DataObject{
	public $__table = 'analytics_search';                        // table name
	public $id;
	public $sessionId;
	public $searchType;
	public $scope;
	public $lookfor;
	public $isAdvanced;
	public $facetsApplied;
	public $numResults;
	public $searchTime;
}