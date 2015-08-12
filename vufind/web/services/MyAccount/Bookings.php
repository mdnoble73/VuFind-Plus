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
		global $interface,
		       $user;

//		// Define sorting options
//		$sortOptions = array(
//			'title' => 'Title',
//			'author' => 'Author',
//			'format' => 'Format',
//			'placed' => 'Date Placed',
//			'location' => 'Pickup Location',
//			'status' => 'Status',
//		);

		$bookings = $user->getMyBookings();

		$libraryHoursMessage = Location::getLibraryHoursMessage($user->homeLocationId);
		$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		$interface->assign('recordList', $bookings);

		// Build Page //
		$this->display('bookings.tpl', 'My Scheduled Items');
	}
}