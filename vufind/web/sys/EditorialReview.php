<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class EditorialReview extends DB_DataObject {
	public $__table = 'editorial_reviews';    // table name
	public $editorialReviewId;
	public $recordId;
	public $title;

	public $review;
	public $source;
	public $pubDate;

	/* Storage for Dynamic Properties */
	private $data;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EditorialReview',$k,$v); }

	function keys() {
		return array('editorialReviewId', 'source');
	}

	function formattedPubDate() {

		$publicationDate = getDate($this->pubDate);
		$pDate = $publicationDate["mon"]."/".$publicationDate["mday"]."/".$publicationDate["year"];
		return $pDate;
	}

	function getObjectStructure(){
		global $configArray;
		$structure = array(
		array(
			'property'=>'editorialReviewId', 
			'type'=>'hidden', 
			'label'=>'Id', 
			'description'=>'The unique id of the editorial review in the database', 
			'storeDb' => true, 
			'primaryKey' => true,
		),
		array(
			'property'=>'title', 
			'type'=>'text', 
			'size' => 100,
			'maxLength'=>100, 
			'label'=>'Title', 
			'description'=>'The title of the review is required.', 
			'storeDb' => true, 
			'serverValidation' => 'validateTitle',
			'required' => true,
		),
		array(
			'property'=>'review', 
			'type'=>'html', 
			'allowableTags' => '<p><a><b><em><ul><ol><em><li><strong><i><br>',
			'rows'=>6, 
			'cols'=>80, 
			'label'=>'Review', 
			'description'=>'Review.', 
			'storeDb' => true, 
		),
		array(
			'property'=>'source', 
			'type'=>'text', 
			'size' => 25,
			'maxLength'=>25, 
			'label'=>'Source', 
			'description'=>'Source.', 
			'storeDb' => true, 
		),
		array(
			'property'=>'recordId', 
			'type'=>'text', 
			'size' => 25,
			'maxLength'=>25, 
			'label'=>'Record Id', 
			'description'=>'Record Id.', 
			'storeDb' => true, 
		),
		'pubDate' => array(
			'property'=>'pubDate',
			'type'=>'hidden',
			'label'=>'pubDate',
			'description'=>'pubDate',
			'storeDb' => true,
		),
		);
		return $structure;
	}

	function insert(){
		//Update publication date if it hasn't been set already.
		if (!isset($this->pubDate)){
			$this->pubDate = time();
		}

		$ret = parent::insert();
		
		return $ret;
	}

	function update(){
		$ret =  parent::update();
		
		return $ret;
	}
	
	function delete(){
		$ret =  parent::delete();
		
		return $ret;
	}
}