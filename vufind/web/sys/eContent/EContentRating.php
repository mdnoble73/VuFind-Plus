<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once ROOT_DIR . '/sys/Utils/SwitchDatabase.php';
require_once 'EContentRecord.php';

class EContentRating extends DB_DataObject
{
	public $__table = 'econtent_rating';   // table name
	public $id;
	public $userId;				//int(11)
	public $recordId;			//int(11)
	public $dateRated;    //date
	public $rating;       //int
	protected $average;   //float - the average rating when loaded with getRatingData
	protected $count;     //int - the number of times the rating has been rated when loaded with getRatingData

	/* Static get */
  function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('EContentRating',$k,$v); }

	function keys() {
	    return array('id', 'userId', 'recordId');
 	}


 	/*
 	 * Returns an array of records DB_OBJECTS of recordId by AVG Rating ordered and Limited
 	 */
 	public function getRecordsListAvgRating($orderBy = "DESC", $limit = 30)
 	{
 		SwitchDatabase::switchToEcontent();
 		$records = array();

 		$sql = "SELECT ei.*, AVG(rating) as rate
				FROM econtent_rating er join econtent_item ei on er.recordId = ei.id
				WHERE 1 GROUP BY recordId ORDER BY rate ".$orderBy.", recordId DESC LIMIT ".$limit;

 		$result = mysql_query($sql);
 		while ($row = mysql_fetch_assoc($result)){
 			unset($row['rate']);
 			$econtentRecord = new EContentRecord();
 			$econtentRecord->get($row['id']);
 			$econtentRecord->setFrom($row,'');
 			$records[] = $econtentRecord;
 			unset($econtentRecord);
 		}

 		SwitchDatabase::restoreDatabase();

 		return $records;
 	}

 	function getRatingData($user, $showGraph = false)
 	{
 		require_once ROOT_DIR . '/Drivers/marmot_inc/UserRating.php';

		//Set default rating data
		$ratingData = array(
			'average' => 0,
			'count'   => 0,
			'user'    => 0,
		);

		//Get rating data for the resource
		$sql = "SELECT AVG(rating) average, count(rating) count from econtent_rating where recordId =  '{$this->recordId}'";
		$rating = new EContentRating();
		$rating->query($sql);
		if ($rating->N > 0){
			$rating->fetch();
			$ratingData['average'] = number_format($rating->average, 2);
			$ratingData['count'] = $rating->count;
		}
		//Get user rating
		if (isset($user) && $user != false){
			$rating = new EContentRating();
			$rating->userId = $user->id;
			$rating->recordId = $this->recordId;
			$rating->find();
			if ($rating->N){
				$rating->fetch();
				$ratingData['user'] = $rating->rating;
			}
		}
		return $ratingData;
	}
}
