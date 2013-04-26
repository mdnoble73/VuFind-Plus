<?php
require_once ('File/MARC.php');
/**
 * Class MarcLoader
 *
 * Loads a Marc record from the database or file system as appropriate.
 */
class MarcLoader{
	/**
	 * @param array $record An array of record data from Solr
	 * @return File_MARC_Record
	 */
	public static function loadMarcRecordFromRecord($record){
		if ($record['recordtype'] == 'marc'){
			return MarcLoader::loadMarcRecordByILSId($record['id'], $record['recordtype']);
			$resource->source = 'VuFind';
		}elseif ($record['recordtype'] == 'econtentRecord'){
			$econtentRecord = new EContentRecord();
			$econtentRecord->id = $record['id'];
			if ($econtentRecord->find(true)){
				return MarcLoader::loadMarcRecordByILSId($econtentRecord->ilsId, $record['recordtype']);
			}else{
				return null;
			}
		}else{
			return null;
		}

	}

	/**
	 * @param EContentRecord $econtentRecord An eContent Record to load the Marc Record for
	 * @return File_MARC_Record
	 */
	public static function loadEContentMarcRecord($econtentRecord){
		if ($econtentRecord->ilsId != false){
			return MarcLoader::loadMarcRecordByILSId($econtentRecord->ilsId, 'econtentRecord');
		}else{
			return null;
		}
	}

	/**
	 * @param string $ilsId       The id of the record within the ils
	 * @param string $recordType  The type of the record in the system
	 * @return File_MARC_Record
	 */
	public static function loadMarcRecordByILSId($ilsId, $recordType = 'marc'){
		global $memCache;
		global $configArray;
		$shortId = str_replace('.', '', $ilsId);
		$firstChars = substr($shortId, 0, 4);
		if ($memCache && !isset($_REQUEST['reload'])){
			$marcRecord = $memCache->get('marc_record_' . $shortId);
		}else{
			$marcRecord = false;
		}
		if ($marcRecord == false){
			//First check the file system

			$individualName = $configArray['Reindex']['individualMarcPath'] . "/{$firstChars}/{$shortId}.mrc";
			//echo ($individualName);
			if (isset($configArray['Reindex']['individualMarcPath']) && file_exists($individualName)){
				//$rawMarc = file_get_contents($individualName);
				$marc = new File_MARC($individualName, File_MARC::SOURCE_FILE);
				if (!($marcRecord = $marc->next())) {
					PEAR_Singleton::raiseError(new PEAR_Error('Could not load marc record for record ' . $shortId));
				}else{
					if ($memCache){
						$memCache->set('marc_record_' . $shortId, $marcRecord, 0, $configArray['Caching']['marc_record']);
					}
				}
			}else{
				require_once ROOT_DIR . '/services/MyResearch/lib/Resource.php';
				$resource = new Resource;
				$resource->record_id = $ilsId;
				if ($recordType == 'marc'){
					$resource->source = 'VuFind';
				}elseif ($recordType == 'econtentRecord'){
					$resource->source = 'eContent';
				}
				//$resource->deleted = 0;
				$resource->selectAdd("marc");
				$resource->whereAdd('marc is not null');
				if ($resource->find(true)){
					$marc = trim($resource->marc);
					$marc = preg_replace('/#29;/', "\x1D", $marc);
					$marc = preg_replace('/#30;/', "\x1E", $marc);
					$marc = preg_replace('/#31;/', "\x1F", $marc);
					$marc = preg_replace('/#163;/', "\xA3", $marc);
					$marc = preg_replace('/#169;/', "\xA9", $marc);
					$marc = preg_replace('/#174;/', "\xAE", $marc);
					$marc = preg_replace('/#230;/', "\xE6", $marc);
					$marc = new File_MARC($marc, File_MARC::SOURCE_STRING);

					if (!($marcRecord = $marc->next())) {
						PEAR_Singleton::raiseError(new PEAR_Error('Could not load marc record for record ' . $shortId));
					}else{
						if ($memCache){
							$memCache->set('marc_record_' . $shortId, $marcRecord, 0, $configArray['Caching']['marc_record']);
						}
					}
				}else{
					return null;
				}
			}
		}
		return $marcRecord;
	}
}
?>