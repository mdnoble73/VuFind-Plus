<?php
/**
 * Handles loading asynchronous
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 3:52 PM
 */

class GroupedRecord_AJAX {
	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		if (in_array($method, array('getRelatedRecords'))){
			header('Content-type: text/html');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}

	function getRelatedRecords(){
		global $interface;
		global $configArray;
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			// Setup Search Engine Connection
			$class = $configArray['Index']['engine'];
			$url = $configArray['Index']['url'];
			/** @var SearchObject_Solr $db */
			$db = new $class($url);
			if ($configArray['System']['debugSolr']) {
				$db->debug = true;
			}

			// Retrieve Full record from Solr
			if (!($record = $db->getRecord($id))) {
				PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
			}

			$recordDriver = RecordDriverFactory::initRecordDriver($record);
			$interface->assign('relatedRecords', $recordDriver->getRelatedRecords());
			return $interface->fetch('GroupedWork/relatedRecordPopup.tpl');
		}else{
			return "Unable to load related records";
		}
	}

	function GetEnrichmentInfoJSON(){
		global $configArray;
		global $interface;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);

		$enrichmentResult = array();
		$enrichmentData = $recordDriver->loadEnrichment();

		//Process series data
		$titles = array();
		if (!isset($enrichmentData['novelist']->seriesTitles) || count($enrichmentData['novelist']->seriesTitles) == 0){
			$enrichmentResult['seriesInfo'] = array('titles'=>$titles, 'currentIndex'=>0);
		}else{
			foreach ($enrichmentData['novelist']->seriesTitles as $record){
				$isbn = $record['isbn'];
				if (strpos($isbn, ' ') > 0){
					$isbn = substr($isbn, 0, strpos($isbn, ' '));
				}
				$cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=medium&isn=" . $isbn;
				if (isset($record['id'])){
					$cover .= "&id=" . $record['id'];
				}
				if (isset($record['upc'])){
					$cover .= "&upc=" . $record['upc'];
				}
				if (isset($record['issn'])){
					$cover .= "&issn=" . $record['issn'];
				}
				if (isset($record['format_category'])){
					$cover .= "&category=" . $record['format_category'][0];
				}
				$title = $record['title'];
				if (isset($record['series'])){
					$title .= ' (' . $record['series'] ;
					if (isset($record['volume'])){
						$title .= ' Volume ' . $record['volume'];
					}
					$title .= ')';
				}
				$titles[] = array(
					'id' => isset($record['id']) ? $record['id'] : '',
					'image' => $cover,
					'title' => $title,
					'author' => $record['author']
				);
			}

			foreach ($titles as $key => $rawData){
				if ($rawData['id']){
					if (strpos($rawData['id'], 'econtentRecord') === 0){
						$fullId = $rawData['id'];
						$shortId = str_replace('econtentRecord', '', $rawData['id']);
						$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
								'<a href="' . $configArray['Site']['path'] . "/EcontentRecord/" . $shortId . '" id="descriptionTrigger' . $fullId . '">' .
								"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
								"</a></div>" .
								"<div id='descriptionPlaceholder{$fullId}' style='display:none'></div>";
					}else{
						$shortId = str_replace('.', '', $rawData['id']);
						$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
								'<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . '" id="descriptionTrigger' . $shortId . '">' .
								"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
								"</a></div>" .
								"<div id='descriptionPlaceholder{$shortId}' style='display:none'></div>";
					}
				}else{
					$formattedTitle = "<div id=\"scrollerTitleSeries{$key}\" class=\"scrollerTitle\">" .
							"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
							"</div>";
				}
				$rawData['formattedTitle'] = $formattedTitle;
				$titles[$key] = $rawData;
			}
			$seriesInfo = array('titles' => $titles, 'currentIndex' => $enrichmentData['novelist']->seriesDefaultIndex);
			$enrichmentResult['seriesInfo'] = $seriesInfo;
		}

		//Load go deeper options
		//TODO: Additional go deeper options
		if (isset($library) && $library->showGoDeeper == 0){
			$enrichmentResult['showGoDeeper'] = false;
		}else{
			require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
			$goDeeperOptions = GoDeeperData::getGoDeeperOptions($recordDriver->getCleanISBN(), $recordDriver->getCleanUPC());
			if (count($goDeeperOptions['options']) == 0){
				$enrichmentResult['showGoDeeper'] = false;
			}else{
				$enrichmentResult['showGoDeeper'] = true;
				$enrichmentResult['goDeeperOptions'] = $goDeeperOptions['options'];
			}
		}

		//Related data
		$enrichmentResult['relatedContent'] = $interface->fetch('Record\relatedContent.tpl');

		return json_encode($enrichmentResult);
	}

	function GetGoDeeperData(){
		require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
		$dataType = $_REQUEST['dataType'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$upc = $recordDriver->getCleanUPC();
		$isbn = $recordDriver->getCleanISBN();

		$formattedData = GoDeeperData::getHtmlData($dataType, 'GroupedWork', $isbn, $upc);
		return $formattedData;

	}
} 