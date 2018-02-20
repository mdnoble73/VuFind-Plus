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

		foreach ($result['titles'] as &$groupedWork) {
			$itemAPI = new ItemAPI();
			$_GET['id'] = $groupedWork['id'];
			$groupedWorkRecord = $itemAPI->loadSolrRecord($groupedWork['id']); 
	
			$groupedWork['availableHere'] = false;
			foreach ($groupedWorkRecord['scoping_details_' . $branch] as $item) {
				$item = explode('|',$item);
				$item['availableHere'] = false;
				if ($item[4] == 'true' && $item[5] == 'true') {
					$item['availableHere'] = true;
					$groupedWork['availableHere'] = true;
				}
				$groupedWork['items'][] = array(
					'1_bibIdentifier'	=> $item[0],
					'2_itemIdentifier'	=> $item[1],
					'5_statusGrouped'	=> $item[2],
					'6_status'		=> $item[3],
					'7_availableHere'	=> $item['availableHere']
				);
				foreach ($groupedWorkRecord['item_details'] as $itemDetail) {
					if (strpos($itemDetail, $item[0] . '|' . $item[1]) === 0) {
						$itemDetail = explode('|',$itemDetail);
						$groupedWork['items'][count($groupedWork['items'])-1] += array(
							'8_itemShelfLocation'		=> $itemDetail[2],
							'9_itemLocationCode'		=> $itemDetail[15]
						);
						break;
					}
				}		
				foreach ($groupedWorkRecord['record_details'] as $bibRecord) {
					if (strpos($bibRecord, $item[0]) === 0) {
						$bibRecord = explode('|', $bibRecord);
						$groupedWork['items'][count($groupedWork['items'])-1] += array(
							'3_bibFormat'		=> $bibRecord[1],
							'4_bibFormatCategory'	=> $bibRecord[2]
						);
						break;
					}
				}
				ksort($groupedWork['items'][count($groupedWork['items'])-1]);
			}
		}
		return $result;
	}
}
