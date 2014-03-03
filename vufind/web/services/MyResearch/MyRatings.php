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
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$rating = new UserWorkReview();
		$groupedWork = new GroupedWork();
		$rating->joinAdd($groupedWork);
		$rating->userId = $user->id;
		$rating->find();
		$ratings = array();
		while($rating->fetch()){
			$ratings[] = array(
				'id' =>$rating->id,
				'title' => ucwords($rating->full_title),
				'author' => ucwords($rating->author),
				'rating' => $rating->rating,
				'link' => '/GroupedWork/' . $rating->groupedRecordPermanentId . '/Home',
				'dateRated' => $rating->dateRated,
				'ratingData' => array('user'=>$rating->rating),
			);
		}

		asort($ratings);

		//Load titles the user is not interested in
		$notInterested = array();

		$notInterestedObj = new NotInterested();
		$resource = new Resource();
		$notInterestedObj->joinAdd($resource);
		$notInterestedObj->userId = $user->id;
		$notInterestedObj->deleted = 0;
		$notInterestedObj->selectAdd('user_not_interested.id as user_not_interested_id');
		$notInterestedObj->find();
		while ($notInterestedObj->fetch()){
			if ($notInterestedObj->source == 'VuFind'){
				$link = '/Record/' . $notInterestedObj->record_id;
			}else{
				$link = '/EcontentRecord/' . $notInterestedObj->record_id;
			}
			if ($notInterestedObj->deleted == 0){
				$notInterested[] = array(
					'id' => $notInterestedObj->user_not_interested_id,
					'title' => $notInterestedObj->title,
					'author' => $notInterestedObj->author,
					'dateMarked' => $notInterestedObj->dateMarked,
					'link' => $link
				);
			}
		}


		$interface->assign('ratings', $ratings);
		$interface->assign('notInterested', $notInterested);

		$interface->setPageTitle('My Ratings');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('myRatings.tpl');
		$interface->display('layout.tpl');
	}
}