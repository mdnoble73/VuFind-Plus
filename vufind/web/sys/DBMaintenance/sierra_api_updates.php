<?php
/**
 * Updates related to sierra api implementation for cleanliness
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:25 PM
 */

function getSierraAPIUpdates() {
	return array(
			'sierra_exportLog' => array(
					'title' => 'Sierra API export log',
					'description' => 'Create log for sierra export via api.',
					'sql' => array(
							"CREATE TABLE IF NOT EXISTS sierra_api_export_log(
									`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
									`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
									`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
									`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
									`notes` TEXT COMMENT 'Additional information about the run', 
									PRIMARY KEY ( `id` )
									) ENGINE = INNODB;",
					)
			),
	);
}