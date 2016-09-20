<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ExternalLinkTracking extends DB_DataObject
{
	public $__table = 'external_link_tracking';    // table name
	public $externalLinkId;                      //int(25)
	public $ipAddress;                    //varchar(255)
	public $recordId;                    //varchar(255)
	public $linkUrl;
	public $linkHost;
	public $trackingDate;
	
	function keys() {
		return array('externalLinkId');
	}

}