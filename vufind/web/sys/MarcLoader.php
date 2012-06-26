<?php
require_once ('File/MARC.php');
class MarcLoader{
	public static function loadMarcRecordFromRecord($record){
		global $memcache;
		global $configArray;
		$marcRecord = $memcache->get('marc_record_' . $record['id']);
		if ($marcRecord == false){
			require_once 'services/MyResearch/lib/Resource.php';
			$resource = new Resource;
			$resource->record_id = $record['id'];
			$resource->source = 'VuFind';
			$resource->deleted = 0;
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
					$memcache->set('marc_record_' . $record['id'], $marcRecord, 0, $configArray['Caching']['marc_record']);
				}
			}else{
				return null;
			}
		}
		return $marcRecord;
	} 
	
	public static function loadMarcRecordByILSId($ilsId){
		global $memcache;
		global $configArray;
		$marcRecord = $memcache->get('marc_record_' . $ilsId);
		if ($marcRecord == false){
			require_once 'services/MyResearch/lib/Resource.php';
			$resource = new Resource;
			$resource->record_id = $ilsId;
			$resource->source = 'VuFind';
			if ($resource->find(true)){
				$marc = trim($resource->marc);
				$marc = preg_replace('/#31;/', "\x1F", $marc);
				$marc = preg_replace('/#30;/', "\x1E", $marc);
				$marc = new File_MARC($marc, File_MARC::SOURCE_STRING);
				
				if (!($marcRecord = $marc->next())) {
					PEAR::raiseError(new PEAR_Error('Could not load marc record for record ' . $record['id']));
				}else{
					$memcache->set('marc_record_' . $ilsId, $marcRecord, 0, $configArray['Caching']['marc_record']);
				}
			}else{
				return null;
			}
		}
		return $marcRecord;
	} 
} 
?>