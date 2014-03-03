<?php
/**
 * API functionality related to Grouped Works
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/4/14
 * Time: 9:21 AM
 */

class WorkAPI {
	function launch()
	{
		$method = $_REQUEST['method'];
		if (is_callable(array($this, $method))) {
			$output = json_encode(array('result'=>$this->$method()));
		} else {
			$output = json_encode(array('error'=>"invalid_method '$method'"));
		}

		echo $output;
	}

	function getRatingData($permanentId = null){
		if ($permanentId == null){
			$permanentId = $_REQUEST['id'];
		}

		global $user;
		//Set default rating data
		$ratingData = array(
			'average' => 0,
			'count'   => 0,
			'user'    => 0,
			'num1star' => 0,
			'num2star' => 0,
			'num3star' => 0,
			'num4star' => 0,
			'num5star' => 0,
		);

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$reviewData = new UserWorkReview();
		$reviewData->groupedRecordPermanentId = $permanentId;
		$reviewData->find();
		$totalRating = 0;
		while ($reviewData->fetch()){
			if ($reviewData->rating > 0){
				$totalRating += $reviewData->rating;
				$ratingData['count']++;
				if ($user && $reviewData->userId == $user->id){
					$ratingData['user'] = $reviewData->rating;
				}
				if ($reviewData->rating == 1){
					$ratingData['num1star'] ++;
				}elseif ($reviewData->rating == 2){
					$ratingData['num2star'] ++;
				}elseif ($reviewData->rating == 3){
					$ratingData['num3star'] ++;
				}elseif ($reviewData->rating == 4){
					$ratingData['num4star'] ++;
				}elseif ($reviewData->rating == 5){
					$ratingData['num5star'] ++;
				}
			}
		}
		if ($ratingData['count'] > 0){
			$ratingData['average'] = $totalRating / $ratingData['count'];
			$ratingData['barWidth5Star'] = 100 * $ratingData['num5star'] / $ratingData['count'];
			$ratingData['barWidth4Star'] = 100 * $ratingData['num4star'] / $ratingData['count'];
			$ratingData['barWidth3Star'] = 100 * $ratingData['num3star'] / $ratingData['count'];
			$ratingData['barWidth2Star'] = 100 * $ratingData['num2star'] / $ratingData['count'];
			$ratingData['barWidth1Star'] = 100 * $ratingData['num1star'] / $ratingData['count'];
		}else{
			$ratingData['barWidth5Star'] = 0;
			$ratingData['barWidth4Star'] = 0;
			$ratingData['barWidth3Star'] = 0;
			$ratingData['barWidth2Star'] = 0;
			$ratingData['barWidth1Star'] = 0;
		}
		return $ratingData;
	}

	public function getIsbnsForWork($permanentId = null){
		if ($permanentId == null){
			$permanentId = $_REQUEST['id'];
		}

		$isbns = array();
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkIdentifierRef.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $permanentId;
		if ($groupedWork->find(true)){
			$groupedWorkIdentifier = new GroupedWorkIdentifier();
			$groupedWorkIdentifierRef = new GroupedWorkIdentifierRef();
			$groupedWorkIdentifierRef->grouped_work_id = $groupedWork->id;
			$groupedWorkIdentifier->type = 'isbn';
			$groupedWorkIdentifierRef->joinAdd($groupedWorkIdentifier);
			$groupedWorkIdentifierRef->find();
			if ($groupedWorkIdentifierRef->N > 0){
				while ($groupedWorkIdentifierRef->fetch()){
					$isbns[] = $groupedWorkIdentifierRef->identifier;
				}
			}
		}
		return $isbns;
	}
} 