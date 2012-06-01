<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EContentImportDetailsEntry extends DB_DataObject
{
	public $__table = 'econtent_file_packaging_log';   // table name
	public $id;
	public $filename;
	public $libraryFilename;
	public $publisher;
	public $distributorId;
	public $copies;
	public $dateFound;
	public $econtentRecordId;
	public $econtentItemId;
	public $dateSentToPackaging;
	public $packagingId;
	public $acsError;
	public $acsId;
	public $status;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentImportDetailsEntry',$k,$v); }

	function keys() {
		return array('id');
	}
}
