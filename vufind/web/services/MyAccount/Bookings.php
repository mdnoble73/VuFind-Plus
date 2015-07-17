<?php
/**
 * Created by PhpStorm.
 * User: pbrammeier
 * Date: 7/16/2015
 * Time: 2:01 PM
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyAccount_Bookings extends MyAccount {

	function launch() {
		global $configArray,
		       $interface,
		       $user;

		$ils = $configArray['Catalog']['ils'];

		// Define sorting options
		$sortOptions = array(
			'title' => 'Title',
			'author' => 'Author',
			'format' => 'Format',
			'placed' => 'Date Placed',
			'location' => 'Pickup Location',
			'status' => 'Status',
		);


		$profile = $this->catalog->getMyProfile($user);
		// TODO: getMyProfile called for second time. First time on index.php

		$libraryHoursMessage = Location::getLibraryHoursMessage($profile['homeLocationId']);
		$interface->assign('libraryHoursMessage', $libraryHoursMessage);

		// Build Page //
		$interface->setPageTitle('My Bookingss');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
//		global $library;
//		if (!$library->showDetailedHoldNoticeInformation){
//			$notification_method = '';
//		}else{
//			$notification_method = ($profile['noticePreferenceLabel'] != 'Unknown') ? $profile['noticePreferenceLabel'] : '';
//			if ($notification_method == 'Mail' && $library->treatPrintNoticesAsPhoneNotices){
//				$notification_method = 'Telephone';
//			}
//		}
//		$interface->assign('notification_method', strtolower($notification_method));
		$interface->setTemplate('bookings.tpl');

		//print_r($patron);
		$interface->display('layout.tpl');
	}
}