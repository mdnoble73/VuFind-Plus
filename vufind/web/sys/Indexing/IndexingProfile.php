<?php
/**
 * Includes information for how to index MARC Records.  Allows for the ability to handle multiple data sources.
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/30/2015
 * Time: 1:44 PM
 */

class IndexingProfile extends DB_DataObject{
	public $__table = 'indexing_profiles';    // table name

	public $id;
	public $name;
	public $marcPath;
	public $individualMarcPath;
	public $indexingClass;
	public $recordNumberTag;
	public $recordNumberPrefix;
	public $itemTag;
	public $suppressItemlessBibs;
	public $useItemBaseddCallNumbers;
	public $callNumberPrestamp;
	public $callNumber;
	public $callNumberCutter;
	public $location;
	public $subLocation;
	public $collection;
	public $itemUrl;
	public $barcode;
	public $status;
	public $totalCheckouts;
	public $lastYearCheckouts;
	public $yearToDateCheckouts;
	public $totalRenewals;
	public $iType;
	public $dueDate;
	public $dateCreated;
	public $dateAdded;
	public $dateAddedFormat;
	public $orderStatus;
	public $iCode2;
	public $useICode2Suppression;
	public $itemRecordNumber;
	public $eContentDescriptor;
	public $lastCheckinDate;
	public $orderTag;
	public $orderLocation;
	public $orderLocations;
	public $orderCopies;
	public $orderCode3;

	function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
		);
		return $structure;
	}
}