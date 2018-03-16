<?php
/**
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

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/API/ItemAPI.php';
require_once ROOT_DIR . '/services/API/ListAPI.php';
require_once ROOT_DIR . '/services/API/SearchAPI.php';

class AnodeAPI extends Action {

	function launch() {
		$method = $_REQUEST['method'];
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			if (is_callable(array($this, $_REQUEST['method']))) {
				$output = json_encode(array('result'=>$this->$_REQUEST['method']()),JSON_PRETTY_PRINT);
			} else {
				$output = json_encode(array('error'=>'invalid_method'));
			}
			echo $output;
	}

	/**
	 * Returns information about the titles within a list 
	 * according to the parameters of 
	 * Anode Pika API Description at
	 * https://docs.google.com/document/d/1N_LiYaK56WLWXTIxzDvmwdVQ3WgogopTnHHixKc_2zk
	 *
	 * @param string $listId - The list to show
	 * @param integer $numGroupedWorksToShow - the maximum number of titles that should be shown
	 * @return array
	 */
	function getAnodeListGroupedWorks($listId = NULL, $numGroupedWorksToShow = NULL) {
		if (!$listId) {
			$listId = $_REQUEST['listId'];
		}
		if (!$_REQUEST['numGroupedWorksToShow']) {
			$numTitlesToShow = 25;
		} else {
			$numTitlesToShow = $_REQUEST['numGroupedWorksToShow'];
		}
		if ($_GET['branch'] && in_array($_GET['branch'], array("bl","se"))) {
			$branch = $_GET['branch'];
		} else {
			$branch = "catalog";
		}
		$listAPI = new ListAPI();
		$result = $listAPI->getListTitles($listId, $numGroupedWorksToShow);

		if (!isset($result['titles'])) {
			$result['titles'] = array();
		} else {
			foreach ($result['titles'] as &$groupedWork) {
				$itemAPI = new ItemAPI();
				$_GET['id'] = $groupedWork['id'];
				$groupedWorkRecord = $itemAPI->loadSolrRecord($groupedWork['id']); 
				unset($groupedWork['ratingData']);
				unset($groupedWork['shortId']);
				unset($groupedWork['small_image']);
				unset($groupedWork['titleURL']);
				$groupedWork['rating'] = $groupedWorkRecord['rating'];
				if (isset($groupedWorkRecord['series'][0])) {
					$groupedWork['series'] = $groupedWorkRecord['series'][0];
				}
				if (isset($groupedWorkRecord['genre'])) {
					$groupedWork['genre'] = $groupedWorkRecord['genre'];
				}
				if (isset($groupedWorkRecord['publisher'])) {
					$groupedWork['publisher'] = $groupedWorkRecord['publisher'];
				}
				if (isset($groupedWorkRecord['language'])) {
					$groupedWork['language'] = $groupedWorkRecord['language'];
				}
				if (isset($groupedWorkRecord['literary_form'])) {
					$groupedWork['literary_form'] = $groupedWorkRecord['literary_form'];
				}
				if (isset($groupedWorkRecord['author2-role'])) {
					$groupedWork['contributors'] = $groupedWorkRecord['author2-role'];
				}
				if (isset($groupedWorkRecord['edition'])) {
					$groupedWork['edition'] = $groupedWorkRecord['edition'];
				}
				if (isset($groupedWorkRecord['publishDateSort'])) {
					$groupedWork['published'] = $groupedWorkRecord['publishDateSort'];
				}
				if (isset($groupedWorkRecord['econtent_source_'.$branch])) {
					$groupedWork['econtent_source'] = $groupedWorkRecord['econtent_source_'.$branch];
				}
				if (isset($groupedWorkRecord['econtent_device'])) {
					$groupedWork['econtent_device'] = $groupedWorkRecord['econtent_device'];
				}
				if (isset($groupedWorkRecord['physical'])) {
					$groupedWork['physical'] = $groupedWorkRecord['physical'];
				}
				if (isset($groupedWorkRecord['isbn'])) {
					$groupedWork['isbn'] = $groupedWorkRecord['isbn'];
				}
				$groupedWork['availableHere'] = false;

// TO DO: include MPAA ratings, Explicit Lyrics advisory, etc.
//				$groupedWork['contentRating'] = $groupedWorkRecord['???'];

				foreach ($groupedWorkRecord['scoping_details_' . $branch] as $item) {
					$item = explode('|',$item);
					$item['availableHere'] = false;
					if ($item[4] == 'true' && $item[5] == 'true') {
						$item['availableHere'] = true;
						$groupedWork['availableHere'] = true;
					}
					$groupedWork['items'][] = array(
						'01_bibIdentifier'	=> $item[0],
						'02_itemIdentifier'	=> $item[1],
						'05_statusGrouped'	=> $item[2],
						'06_status'		=> $item[3],
						'07_availableHere'	=> $item['availableHere']
					);
					foreach ($groupedWorkRecord['item_details'] as $itemDetail) {
						if (strpos($itemDetail, $item[0] . '|' . $item[1]) === 0) {
							$itemDetail = explode('|',$itemDetail);
							$groupedWork['items'][count($groupedWork['items'])-1] += array(
								'08_itemShelfLocation'		=> $itemDetail[2],
								'09_itemLocationCode'		=> $itemDetail[15],
								'10_itemCallNumber'		=> $itemDetail[3]
							);
							break;
						}
					}		
					foreach ($groupedWorkRecord['record_details'] as $bibRecord) {
						if (strpos($bibRecord, $item[0]) === 0) {
							$bibRecord = explode('|', $bibRecord);
							$groupedWork['items'][count($groupedWork['items'])-1] += array(
								'03_bibFormat'		=> $bibRecord[1],
								'04_bibFormatCategory'	=> $bibRecord[2]
							);
							break;
						}
					}
					ksort($groupedWork['items'][count($groupedWork['items'])-1]);
				}
			}
		}
		return $result;
	}
}
