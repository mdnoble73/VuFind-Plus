<?php
/**
 * Table Definition for EContentHold
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'sys/SolrDataObject.php';

class EContentCheckout extends DB_DataObject {
	public $__table = 'econtent_checkout';    // table name
	public $id;
	public $dateCheckedOut;
	public $dateDue;
	public $dateReturned;
	public $dateFulfilled;
	public $recordId;
	public $userId;
	public $status; //Out, Returned
	public $acsDownloadLink;
	public $downloadedToReader;
	public $acsTransactionId;
	public $userAcsId;
	
	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('econtent_checkout',$k,$v); }

	function keys() {
		return array('id', 'userId');
	}
}
