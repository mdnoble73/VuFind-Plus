<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once 'sys/Utils/SwitchDatabase.php';
require_once 'EContentRecord.php';

class EContentRating extends DB_DataObject 
{
	public $__table = 'econtent_rating';   // table name
	public $id;
	public $userId;				//int(11)
	public $recordId;			//int(11)
	public $dateRated;  //date
	public $rating;  //date
	
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
 		require_once 'Drivers/marmot_inc/UserRating.php';

		//Set default rating data
		$ratingData = array(
            'average' => 0,
            'count'   => 0,
            'user'    => 0,
            'summary' => array(
                'fiveStar'   => 0,
                'fourStar'   => 0,
                'threeStar'   => 0,
                'twoStar'   => 0,
                'oneStar'   => 0,
		),
		);

		//Get rating data for the resource
		$sql = "SELECT AVG(rating) average, count(rating) count, sum(1star) num1star, sum(2star) num2star, sum(3star) num3star, sum(4star) num4star, sum(5star) num5star FROM (SELECT rating, (rating = 1) as 1star, (rating = 2) as 2star, (rating = 3) as 3star, (rating = 4) as 4star, (rating = 5) as 5star from econtent_rating where recordId =  '{$this->recordId}') ratingData";
		$rating = new EContentRating();
		$rating->query($sql);
		if ($rating->N > 0){
			$rating->fetch();
			$ratingData['average'] = number_format($rating->average, 2);
			$ratingData['count'] = $rating->count;
			$ratingData['summary']['oneStar'] = $rating->num1star == null ? 0 :  $rating->num1star;
			$ratingData['summary']['twoStar'] = $rating->num2star == null ? 0 :  $rating->num2star;
			$ratingData['summary']['threeStar'] = $rating->num3star == null ? 0 :  $rating->num3star;
			$ratingData['summary']['fourStar'] = $rating->num4star == null ? 0 :  $rating->num4star;
			$ratingData['summary']['fiveStar'] = $rating->num5star == null ? 0 :  $rating->num5star;
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

		//Create a graph of the individual ratings
		if ($showGraph){
			$ratingData['summaryGraph'] = $this->createRatingGraph($ratingData);
		}

		return $ratingData;
	}
}
