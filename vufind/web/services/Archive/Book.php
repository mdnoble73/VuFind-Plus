<?php
/**
 * Allows display of a single image from Islandora
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/8/2015
 * Time: 8:43 PM
 */

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Book extends Archive_Object{
	function launch() {
		global $interface;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

		//Get the contents of the book
		$bookContents = $this->loadBookContents();
		$interface->assign('bookContents', $bookContents);

		$interface->assign('showExploreMore', true);

		//Get the active page pid
		if (isset($_REQUEST['page'])){
			$interface->assign('activePage', $_REQUEST['page']);
		}else{
			//Get the first page from the contents
			foreach($bookContents as $section){
				if (count($section['pages'])){
					$firstPage = reset($section['pages']);
					$interface->assign('activePage', $firstPage['pid']);
					break;
				}else{
					$interface->assign('activePage', $section['pid']);
					break;
				}
			}
		}

		if (isset($_REQUEST['viewer'])){
			$interface->assign('activeViewer', $_REQUEST['viewer']);
		}else{
			$interface->assign('activeViewer', 'image');
		}

		// Display Page
		$this->display('book.tpl');
	}

	private function loadBookContents() {
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];
		$rels_predicate = 'isConstituentOf';
		$sections = array();

		$fedoraUtils = FedoraUtils::getInstance();

		$escaped_pid = str_replace(':', '_', $this->pid);
		$query = <<<EOQ
PREFIX islandora-rels-ext: <http://islandora.ca/ontology/relsext#>
SELECT ?object ?title ?seq
FROM <#ri>
WHERE {
  ?object <fedora-model:label> ?title ;
          <fedora-rels-ext:$rels_predicate> <info:fedora/{$this->pid}> .
  OPTIONAL {
    ?object islandora-rels-ext:isSequenceNumberOf$escaped_pid ?seq
  }
}
EOQ;

		$queryResults = $fedoraUtils->doSparqlQuery($query);

		// Sort the objects into their proper order.
		$sort = function($a, $b) {
			$a = $a['seq']['value'];
			$b = $b['seq']['value'];
			if ($a === $b) {
				return 0;
			}
			if (empty($a)) {
				return 1;
			}
			if (empty($b)) {
				return -1;
			}
			return $a - $b;
		};
		uasort($queryResults, $sort);

		foreach ($queryResults as $result) {
			$objectPid = $result['object']['value'];
			//TODO: check access
			/** @var FedoraObject $sectionObject */
			$sectionObject = $fedoraUtils->getObject($objectPid);
			$sectionDetails = array(
					'pid' => $objectPid,
					'title' => $result['title']['value'],
					'seq' => $result['seq']['value'],
					'cover' => $fedoraUtils->getObjectImageUrl($sectionObject, 'thumbnail')
			);
			$pdfStream = $sectionObject->getDatastream('PDF');
			if ($pdfStream != null){
				$sectionDetails['pdf'] = $objectUrl . '/' . $objectPid . '/datastream/PDF/view';;
			}
			//Load individual pages for this section
			$sectionDetails = $this->loadPagesForSection($sectionObject, $sectionDetails);

			$sections[$objectPid] = $sectionDetails;
		}

		return $sections;

	}

	/**
	 * Main logic happily borrowed from islandora_paged_content/includes/utilities.inc
	 * @param FedoraObject $sectionObject
	 * @param array $sectionDetails
	 * @return array
	 */
	function loadPagesForSection($sectionObject, $sectionDetails){
		global $configArray;
		$objectUrl = $configArray['Islandora']['objectUrl'];

		$fedoraUtils = FedoraUtils::getInstance();
		$query = <<<EOQ
PREFIX islandora-rels-ext: <http://islandora.ca/ontology/relsext#>
SELECT ?pid ?page ?label ?width ?height
FROM <#ri>
WHERE {
  ?pid <fedora-rels-ext:isMemberOf> <info:fedora/{$sectionObject->id}> ;
       <fedora-model:label> ?label ;
       islandora-rels-ext:isSequenceNumber ?page ;
       <fedora-model:state> <fedora-model:Active> .
  OPTIONAL {
    ?pid <fedora-view:disseminates> ?dss .
    ?dss <fedora-view:disseminationType> <info:fedora/*/JP2> ;
         islandora-rels-ext:width ?width ;
         islandora-rels-ext:height ?height .
 }
}
ORDER BY ?page
EOQ;

		$results = $fedoraUtils->doSparqlQuery($query);

		// Get rid of the "extra" info...
		$map = function($o) {
			foreach ($o as $key => &$info) {
				$info = $info['value'];
			}

			$o = array_filter($o);

			return $o;
		};
		$pages = array_map($map, $results);

		// Sort the pages into their proper order.
		$sort = function($a, $b) {
			$a = (is_array($a) && isset($a['page'])) ? $a['page'] : 0;
			$b = (is_array($b) && isset($b['page'])) ? $b['page'] : 0;
			if ($a == $b) {
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		};
		uasort($pages, $sort);

		foreach ($pages as $index=>$page){
			//Get additional details about the page
			$pageObject = $fedoraUtils->getObject($page['pid']);
			if ($pageObject->getDataStream('JP2') != null){
				$page['jp2'] = $objectUrl . '/' . $page['pid'] . '/datastream/JP2/view';
			}
			if ($pageObject->getDataStream('PDF') != null){
				$page['pdf'] = $objectUrl . '/' . $page['pid'] . '/datastream/PDF/view';
			}
			if ($pageObject->getDataStream('HOCR') != null){
				$page['transcript'] = $objectUrl . '/' . $page['pid'] . '/datastream/HOCR/view';
			}elseif ($pageObject->getDataStream('OCR') != null){
				$page['transcript'] = $objectUrl . '/' . $page['pid'] . '/datastream/OCR/view';
			}
			$page['cover'] = $fedoraUtils->getObjectImageUrl($pageObject, 'thumbnail');
			$pages[$index] = $page;
		}

		$sectionDetails['pages'] = $pages;

		return $sectionDetails;
	}
}