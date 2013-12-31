<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/31/13
 * Time: 11:00 AM
 */

class OverDriveAPIProductFormats extends DB_DataObject {
	public $__table = 'overdrive_api_product_formats';   // table name

	public $id;
	public $productId;
	public $textId;
	public $numericId;
	public $name;
	public $fileName;
	public $fileSize;
	public $partCount;
	public $sampleSource_1;
	public $sampleUrl_1;
	public $sampleSource_2;
	public $sampleUrl_2;
} 