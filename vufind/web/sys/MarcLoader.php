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
		}elseif ($record['recordtype'] == 'econtentRecord'){
			require_once ROOT_DIR . '/sys/eContent/EContentRecord.php';
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
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		$shortId = str_replace('.', '', $ilsId);
		if (strlen($shortId) < 9){
			$shortId = str_pad($shortId, 9, "0", STR_PAD_LEFT);
		}
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
			}
		}
		return $marcRecord;
	}
}
?>