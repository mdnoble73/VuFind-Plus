<?php
/**
 * A page to display any ratings that the user has done
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/1/13
 * Time: 9:58 AM
 */
require_once 'MyResearch.php';
class MyRatings extends MyResearch{
	public function launch(){
		global $interface;
		global $user;

		//Load user ratings
		require_once ROOT_DIR . '/Drivers/marmot_inc/UserRating.php';
		$rating = new UserRating();
		$resource = new Resource();
		$rating->joinAdd($resource);
		$rating->userid = $user->id;
		$rating->find();
		$ratings = array();
		while($rating->fetch()){
			if ($rating->deleted == 0){
				$ratings[] = array(
					'id' =>$rating->id,
					'title' => $rating->title,
					'author' => $rating->author,
					'format' => $rating->format,
					'rating' => $rating->rating,
					'resourceId' => $rating->resourceid,
					'fullId' => $rating->record_id,
					'shortId' => $rating->shortId,
					'link' => '/Record/' . $rating->record_id . '/Home',
					'dateRated' => $rating->dateRated,
					'ratingData' => array('user'=>$rating->rating),
					'source' => 'VuFind'
				);
			}
		}

		//Load econtent ratings
		require_once ROOT_DIR . '/sys/eContent/EContentRating.php';
		$eContentRating = new EContentRating();
		$econtentRecord = new EContentRecord();
		$eContentRating->joinAdd($econtentRecord);
		$eContentRating->userId = $user->id;
		$eContentRating->find();
		while ($eContentRating->fetch()){
			if ($eContentRating->status == 'active'){
				$resource = new Resource();
				$resource->record_id = $eContentRating->id;
				$resource->source = 'eContent';
				$resource->find(true);
				$ratings[] = array(
					'id' =>$eContentRating->id,
					'title' => $eContentRating->title,
					'author' => $eContentRating->author,
					'format' => $resource->format_category,
					'rating' => $eContentRating->rating,
					//'resourceId' => $eContentRating->resourceid,
					'fullId' => $eContentRating->id,
					'shortId' => $eContentRating->id,
					'link' => '/EcontentRecord/' . $eContentRating->id . '/Home',
					'dateRated' => $eContentRating->dateRated,
					'ratingData' => array('user'=>$eContentRating->rating),
					'source' => 'eContent'
				);
			}
		}

		asort($ratings);

		//Load titles the user is not interested in
		$notInterested = array();

		$notInterestedObj = new NotInterested();
		$resource = new Resource();
		$notInterestedObj->joinAdd($resource);
		$notInterestedObj->userId = $user->id;
		$notInterestedObj->selectAdd('user_not_interested.id as user_not_interested_id');
		$notInterestedObj->find();
		while ($notInterestedObj->fetch()){
			if ($notInterestedObj->source == 'VuFind'){
				$link = '/Record/' . $notInterestedObj->record_id;
			}else{
				$link = '/EcontentRecord/' . $notInterestedObj->record_id;
			}
			$notInterested[] = array(
				'id' => $notInterestedObj->user_not_interested_id,
				'title' => $notInterestedObj->title,
				'author' => $notInterestedObj->author,
				'dateMarked' => $notInterestedObj->dateMarked,
				'link' => $link
			);
		}


		$interface->assign('ratings', $ratings);
		$interface->assign('notInterested', $notInterested);

		$interface->setPageTitle('My Ratings');
		$interface->setTemplate('myRatings.tpl');
		$interface->display('layout.tpl');
	}
}