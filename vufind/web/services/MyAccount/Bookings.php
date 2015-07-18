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

		$bookings = $this->catalog->getMyBookings();
		global $profile;
		$profile = $this->catalog->getMyProfile($user);
		// TODO: getMyProfile called for second time. First time on index.php

		$libraryHoursMessage = Location::getLibraryHoursMessage($profile['homeLocationId']);
		$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		$interface->assign('recordList', $bookings);


		// Build Page //
		$interface->setPageTitle('My Bookings');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('bookings.tpl');

		//print_r($patron);
		$interface->display('layout.tpl');
	}
}