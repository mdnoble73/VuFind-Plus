<?php
/**
 * Updates related to islandora for cleanliness
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:25 PM
 */

function getHooplaUpdates() {
	return array(
			'variables_lastHooplaExport' => array(
					'title' => 'Variables Last Hoopla Export Time',
					'description' => 'Add a variable for when hoopla data was extracted from the API last.',
					'sql' => array(
							"INSERT INTO variables (name, value) VALUES ('lastHooplaExport', 'false')",
					),
			),
	);
}