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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 */

require_once ROOT_DIR . '/Action.php';

class AJAX extends Action {

	function launch()
	{
		global $analytics;
		$analytics->disableTracking();
		$method = $_REQUEST['method'];
		header ('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (is_callable(array($this, $method))) {
			try{
				$result = $this->$method();
				require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
				$utf8EncodedValue = ArrayUtils::utf8EncodeArray($result);
				$output = json_encode($utf8EncodedValue);
				$error = json_last_error();
				if ($error != JSON_ERROR_NONE || $output === FALSE){
					if (function_exists('json_last_error_msg')){
						$output = json_encode(array('error'=>'error_encoding_data', 'message' => json_last_error_msg()));
					}else{
						$output = json_encode(array('error'=>'error_encoding_data', 'message' => json_last_error()));
					}
					global $configArray;
					if ($configArray['System']['debug']){
						print_r($utf8EncodedValue);
					}
				}
			}catch (Exception $e){
				$output = json_encode(array('error'=>'error_encoding_data', 'message' => $e));
				global $logger;
				$logger->log("Error encoding json data $e", PEAR_LOG_ERR);
			}

		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}
		echo $output;
	}

	function getCombinedResults()
	{
		$source = $_REQUEST['source'];
		$numberOfResults = $_REQUEST['numberOfResults'];
		$sectionId = $_REQUEST['id'];
		list($className, $id) = explode(':', $sectionId);
		$sectionObject = null;
		if ($className == 'LibraryCombinedResultSection'){
			$sectionObject = new LibraryCombinedResultSection();
			$sectionObject->id = $id;
			$sectionObject->find(true);
		}elseif ($className == 'LocationCombinedResultSection'){
			$sectionObject = new LocationCombinedResultSection();
			$sectionObject->id = $id;
			$sectionObject->find(true);
		}else{
			return array(
					'success' => false,
					'error' => 'Invalid section id pased in'
			);
		}
		$searchTerm = $_REQUEST['searchTerm'];
		$searchType = $_REQUEST['searchType'];
		$hideCovers = $_REQUEST['hideCovers'];

		$results = "<div>Showing $numberOfResults for $source.  Hide covers? $hideCovers</div>";
		$results .= "<div><a href='" . $sectionObject->getResultsLink($searchTerm, $searchType) . "'>Full Results</a></div>";
		return array(
				'success' => true,
				'results' => $results
		);
	}
}
