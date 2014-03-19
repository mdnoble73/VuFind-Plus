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
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$rating = new UserWorkReview();
		$rating->userId = $user->id;
		$rating->find();
		$ratings = array();
		while($rating->fetch()){
			$groupedWorkDriver = new GroupedWorkDriver($rating->groupedRecordPermanentId);
			if ($groupedWorkDriver->isValid){
				$ratings[] = array(
					'id' =>$rating->id,
					'groupedWorkId' => $rating->groupedRecordPermanentId,
					'title' => $groupedWorkDriver->getTitle(),
					'author' => $groupedWorkDriver->getPrimaryAuthor(),
					'rating' => $rating->rating,
					'review' => $rating->review,
					'link' => $groupedWorkDriver->getLinkUrl(),
					'dateRated' => $rating->dateRated,
					'ratingData' => $groupedWorkDriver->getRatingData(),
				);
			}
		}

		asort($ratings);

		//Load titles the user is not interested in
		$notInterested = array();

		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterestedObj = new NotInterested();
		$notInterestedObj->userId = $user->id;
		$notInterestedObj->find();
		while ($notInterestedObj->fetch()){
			$groupedWorkId = $notInterestedObj->groupedRecordPermanentId;
			$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId) ;
			if ($groupedWorkDriver->isValid){
				$notInterested[] = array(
					'id' => $notInterestedObj->id,
					'title' => $groupedWorkDriver->getTitle(),
					'author' => $groupedWorkDriver->getPrimaryAuthor(),
					'dateMarked' => $notInterestedObj->dateMarked,
					'link' => $groupedWorkDriver->getLinkUrl()
				);
			}
		}

		$interface->assign('ratings', $ratings);
		$interface->assign('notInterested', $notInterested);
		$interface->assign('showNotInterested', false);

		$interface->setPageTitle('My Ratings');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('myRatings.tpl');
		$interface->display('layout.tpl');
	}
}