<?php
/**
 * Updates related to user tables for cleanliness
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:42 PM
 */

function getUserUpdates(){
	return array(
		'roles_1' => array(
			'title' => 'Roles 1',
			'description' => 'Add new role for epubAdmin',
			'sql' => array(
				"INSERT INTO roles (name, description) VALUES ('epubAdmin', 'Allows administration of eContent.')",
			),
		),

		'roles_2' => array(
			'title' => 'Roles 2',
			'description' => 'Add new role for locationReports',
			'sql' => array(
				"INSERT INTO roles (name, description) VALUES ('locationReports', 'Allows the user to view reports for their location.')",
			),
		),

		'user_display_name' => array(
			'title' => 'User display name',
			'description' => 'Add displayName field to User table to allow users to have aliases',
			'sql' => array(
				"ALTER TABLE user ADD displayName VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_phone' => array(
			'title' => 'User phone',
			'description' => 'Add phone field to User table to allow phone numbers to be displayed for Materials Requests',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD phone VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_ilsType' => array(
			'title' => 'User Type',
			'description' => 'Add patronType field to User table to allow for functionality to be controlled based on the type of patron within the ils',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD patronType VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_overdrive_email' => array(
			'title' => 'User OverDrive Email',
			'description' => 'Add overdriveEmail field to User table to allow for patrons to use a different email fo notifications when their books are ready',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD overdriveEmail VARCHAR( 250 ) NOT NULL DEFAULT ''",
				"ALTER TABLE user ADD promptForOverdriveEmail TINYINT DEFAULT 1",
				"UPDATE user SET overdriveEmail = email"
			),
		),

		'user_preferred_library_interface' => array(
			'title' => 'User Preferred Library Interface',
			'description' => 'Add preferred library interface to ',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD preferredLibraryInterface INT(11) DEFAULT NULL",
			),
		),
	);
}