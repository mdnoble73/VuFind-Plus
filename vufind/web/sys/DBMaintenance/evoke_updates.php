<?php
/**
 * Updates related to eVoke for cleanliness
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:25 PM
 */

function getEVokeUpdates(){
	return array(
		'evoke_setup' => array(
			'title' => 'Initial eVoke Setup',
			'description' => 'Store data about evoke records and items  (loanables) for use during indexing and grouping',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS evoke_record (
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					evoke_id CHAR(8) NOT NULL,
					dateAdded int(11),
					dateUpdated int(11),
					deleted tinyint(4),
					dateDeleted int(11),
					loanableTypes VARCHAR(255),
					totalCopies int(11),
					availableCopies int(11),
					PRIMARY KEY (id),
					UNIQUE KEY (evoke_id),
					KEY (dateUpdated),
					KEY (deleted)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8",
			),
		),
	);
}