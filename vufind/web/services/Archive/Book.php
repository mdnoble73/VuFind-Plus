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

		// Display Page
		$this->display('book.tpl');
	}

	private function loadBookContents() {
		$rels_predicate = 'isConstituentOf';
		$objects = array();

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
			$archiveObject = $fedoraUtils->getObject($objectPid);
			$objects[$objectPid] = array(
					'pid' => $objectPid,
					'title' => $result['title']['value'],
					'seq' => $result['seq']['value'],
					'cover' => $fedoraUtils->getObjectImageUrl($archiveObject, 'thumbnail')
			);
		}

		return $objects;

	}


}