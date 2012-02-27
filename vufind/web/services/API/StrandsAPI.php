<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';
require_once 'CatalogConnection.php';

class StrandsAPI extends Action {

	function launch()
	{
		global $configArray;

		//header('Content-type: application/json');
		header('Content-type: text/plain');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (is_callable(array($this, $_GET['method']))) {
			$output = $this->$_GET['method']();
		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}

		echo $output;
	}

	/**
	 * Get ratings as a tab separated file
	 *
	 * First column : ItemId
	 * Second Column : Rating Average (should be between 0 and 5, decimals are accepted)
	 * Third column : Total votes

	 * It is VERY important that the frist column represents the "itemId"
	 * has the same id that the one you are sending us with your catalog,
	 * because that is what we use to link your products with its ratings
	 */
	function getRatingInfo() {
		require_once 'Drivers/marmot_inc/UserRating.php';
		$ratings = new UserRating();
		$ratings->query("SELECT record_id, AVG(rating) as averageRating, count(resource.id) as numRatings from user_rating INNER JOIN resource on resourceid = resource.id GROUP BY record_id");
		$tabbedData = '';
		while ($ratings->fetch()){
			$tabbedData .= "{$ratings->record_id}\t{$ratings->averageRating}\t{$ratings->numRatings}\r\n";
		}

		//Get eContent Ratings as well
		require_once('sys/eContent/EContentRating.php');
		$eContentRatings = new EContentRating();
		$eContentRatings->query("SELECT recordId, AVG(rating)as averageRating, count(id) as numRatings FROM `econtent_rating` GROUP BY recordId");
		while ($eContentRatings->fetch()){
			$tabbedData .= "econtentRecord{$eContentRatings->recordId}\t{$eContentRatings->averageRating}\t{$eContentRatings->numRatings}\r\n";
		}

		return $tabbedData;
	}
}