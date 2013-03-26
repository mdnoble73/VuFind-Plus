<?php
require_once ('File/MARC.php');
class MarcLoader{
	public static function loadMarcRecordFromRecord($record){
		if ($record['recordtype'] == 'marc'){
			return MarcLoader::loadMarcRecordByILSId($record['id']);
			$resource->source = 'VuFind';
		}elseif ($record['recordtype'] == 'econtentRecord'){
			$econtentRecord = new EContentRecord();
			$econtentRecord->id = $record['id'];
			if ($econtentRecord->find(true)){
				return MarcLoader::loadMarcRecordByILSId($econtentRecord->ilsId);
			}else{
				return null;
			}
		}else{
			return null;
		}

	}

	public static function loadEContentMarcRecord($econtentRecord){
		if ($econtentRecord->ilsId != false){
			echo("Loading marc record for econtentrecord");
			return MarcLoader::loadMarcRecordByILSId($econtentRecord->ilsId);
		}else{
			return null;
		}
	}

	public static function loadMarcRecordByILSId($ilsId){
		global $memcache;
		global $configArray;
		$shortId = str_replace('.', '', $ilsId);
		$firstChars = substr($shortId, 0, 4);
		if ($memcache && !isset($_REQUEST['reload'])){
			$marcRecord = $memcache->get('marc_record_' . $shortId);
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
					PEAR::raiseError(new PEAR_Error('Could not load marc record for record ' . $shortId));
				}else{
					if ($memcache){
						$memcache->set('marc_record_' . $shortId, $marcRecord, 0, $configArray['Caching']['marc_record']);
					}
				}
			}else{
				require_once 'services/MyResearch/lib/Resource.php';
				$resource = new Resource;
				$resource->record_id = $ilsId;
				if ($record['recordtype'] == 'marc'){
					$resource->source = 'VuFind';
				}elseif ($record['recordtype'] == 'econtentRecord'){
					$resource->source = 'eContent';
				}
				//$resource->deleted = 0;
				$resource->selectAdd("marc");
				$resource->whereAdd('marc is not null');
				if ($resource->find(true)){
					$marc = trim($resource->marc);
					/*for ($i = 0; $i <= 31; $i++ ){
						$marc = preg_replace("/#{$i};/", "\x" . dechex($i) , $marc);
					}
					for ($i = 127; $i <= 255; $i++ ){
						$marc = preg_replace("/#{$i};/", "\x" . dechex($i) , $marc);
					}*/
					$marc = preg_replace('/#29;/', "\x1D", $marc);
					$marc = preg_replace('/#30;/', "\x1E", $marc);
					$marc = preg_replace('/#31;/', "\x1F", $marc);
					$marc = preg_replace('/#163;/', "\xA3", $marc);
					$marc = preg_replace('/#169;/', "\xA9", $marc);
					$marc = preg_replace('/#174;/', "\xAE", $marc);
					$marc = preg_replace('/#230;/', "\xE6", $marc);
					$marc = new File_MARC($marc, File_MARC::SOURCE_STRING);

					if (!($marcRecord = $marc->next())) {
						PEAR::raiseError(new PEAR_Error('Could not load marc record for record ' . $record['id']));
					}else{
						if ($memcache){
							$memcache->set('marc_record_' . $record['id'], $marcRecord, 0, $configArray['Caching']['marc_record']);
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