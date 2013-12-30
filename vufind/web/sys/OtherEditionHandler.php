<?php
/**
 * 
 * Handles loading information about other editions of a title based on information in the original record.
 * 
 * @author Mark Noble
 *
 */
class OtherEditionHandler{
	static function getEditions($sourceSolrId, $isbn, $issn, $numResourcesToLoad = 5) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$editions = $memCache->get('other_editions_' . $isbn);
		if (!$editions || isset($_REQUEST['reload'])){
			
			// Setup Search Engine Connection
			$class = $configArray['Index']['engine'];
			/** @var Solr $db */
			$db = new $class($configArray['Index']['url']);

			if ($isbn) {
				if ($configArray['Content']['otherEditions'] == 'LibraryThing'){
					$editions = self::getLibraryThingRelatedRecords($sourceSolrId, $isbn, $numResourcesToLoad, $db);
				}else{
					$editions = self::getXISBN($sourceSolrId, $isbn, $numResourcesToLoad, $db);
				}
			} else if ($issn) {
				$editions = self::getXISSN($sourceSolrId, $issn, $numResourcesToLoad, $db);
			}else{
				$editions = null;
			}
			
			$memCache->set('other_editions_' . $isbn, $editions, 0, $configArray['Caching']['other_editions']);
		}
		return $editions;
	}

	/**
	 * @param string $sourceSolrId
	 * @param string $isbn
	 * @param integer $numResourcesToLoad
	 * @param Solr $db
	 * @return null
	 */
	private static function getLibraryThingRelatedRecords($sourceSolrId, $isbn, $numResourcesToLoad, $db){
		$url = "http://www.librarything.com/api/thingISBN/$isbn" ;

		//Load data from xml file
		$xml = simplexml_load_file($url);
		$query = '';
		foreach ($xml->isbn as $isbn){
			if ($query != '') {
				$query .= ' OR isbn:' . $isbn;
			} else {
				$query = 'isbn:' . $isbn;
			}
		}

		if (isset($query) && ($query != '')) {
			// Filter out current record
			$query .= ' NOT id:' . $sourceSolrId;

			$result = $db->search($query, null, null, 0, $numResourcesToLoad);
			if (!PEAR_Singleton::isError($result)) {
				if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
					return $result['response']['docs'];
				} else {
					return null;
				}
			} else {
				return $result;
			}
		} else {
			return null;
		}
	}

	/**
	 * @param string $sourceSolrId
	 * @param string $isbn
	 * @param integer $numResourcesToLoad
	 * @param Solr $db
	 * @return null
	 */
	private static function getXISBN($sourceSolrId, $isbn, $numResourcesToLoad, $db) {
		global $configArray;

		// Build URL
		$url = 'http://xisbn.worldcat.org/webservices/xid/isbn/' . urlencode(is_array($isbn) ? $isbn[0] : $isbn) .
               '?method=getEditions&format=csv';
		if (isset($configArray['WorldCat']['id'])) {
			$url .= '&ai=' . $configArray['WorldCat']['id'];
		}

		// Print Debug code
		if ($configArray['System']['debug']) {
			global $logger;
			$logger->log("<pre>XISBN: $url</pre>", PEAR_LOG_INFO);
		}

		// Fetch results
		$query = '';
		if ($fp = @fopen($url, "r")) {
			while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
				// If we got an error message, don't treat it as an ISBN!
				if ($data[0] == 'overlimit') {
					continue;
				}
				if ($query != '') {
					$query .= ' OR isbn:' . $data[0];
				} else {
					$query = 'isbn:' . $data[0];
				}
			}
		}
		if (strlen($query) == 0) {
			//Get ISBNs from the record itself
			$record = $db->getRecord($sourceSolrId);
			if ($record){
				if (isset($record['isbn'])){
					$isbns = is_array($record['isbn']) ? $record['isbn'] : array($record['isbn']);
					foreach ($isbns as $tmpIsbn){
						$tmpIsbn = ISBN::normalizeISBN($tmpIsbn);
						if (strlen($tmpIsbn) > 0){
							if ($query != '') {
								$query .= ' OR isbn:' .$tmpIsbn;
							} else {
								$query = 'isbn:' . $tmpIsbn;
							}
						}
					}
				}
				if (isset($record['grouping_term'])){
					$query .= ' OR grouping_term:"' . $record['grouping_term'] . '"';
				}
			}
		}

		if ($query != '') {
			// Filter out current record
			$query .= ' NOT id:' . $sourceSolrId;

			$result = $db->search($query, null, null, 0, $numResourcesToLoad);
			if (!PEAR_Singleton::isError($result)) {
				if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
					return $result['response']['docs'];
				} else {
					return null;
				}
			} else {
				return $result;
			}
		} else {
			return null;
		}
	}

	/**
	 * @param string $sourceSolrId
	 * @param string $issn
	 * @param integer $numResourcesToLoad
	 * @param Solr $db
	 * @return null
	 */
	private static function getXISSN($sourceSolrId, $issn, $numResourcesToLoad, $db) {
		global $configArray;

		// Build URL
		$url = 'http://xissn.worldcat.org/webservices/xid/issn/' . urlencode(is_array($issn) ? $issn[0] : $issn) .
		//'?method=getEditions&format=csv';
               '?method=getEditions&format=xml';
		if (isset($configArray['WorldCat']['id'])) {
			$url .= '&ai=' . $configArray['WorldCat']['id'];
		}

		// Print Debug code
		if ($configArray['System']['debug']) {
			global $logger;
			$logger->log("<pre>XISSN: $url</pre>", PEAR_LOG_INFO);
		}

		// Fetch results
		$query = '';
		$data = @file_get_contents($url);
		if (empty($data)) {
			return null;
		}
		$unxml = new XML_Unserializer();
		$unxml->unserialize($data);
		$data = $unxml->getUnserializedData($data);
		if (!empty($data) && isset($data['group']['issn'])) {
			if (is_array($data['group']['issn'])) {
				foreach ($data['group']['issn'] as $issn) {
					if ($query != '') {
						$query .= ' OR issn:' . $issn;
					} else {
						$query = 'issn:' . $issn;
					}
				}
			} else {
				$query = 'issn:' . $data['group']['issn'];
			}
		}

		if ($query) {
			// Filter out current record
			$query .= ' NOT id:' . $sourceSolrId;

			$result = $db->search($query, null, null, 0, $numResourcesToLoad);
			if (!PEAR_Singleton::isError($result)) {
				if (isset($result['response']['docs']) && !empty($result['response']['docs'])) {
					return $result['response']['docs'];
				} else {
					return null;
				}
			} else {
				return $result;
			}
		} else {
			return null;
		}
	}
}