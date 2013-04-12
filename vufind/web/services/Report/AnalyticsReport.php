<?php
require_once ROOT_DIR . '/services/Report/Report.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_City.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_Country.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_State.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_Theme.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_Device.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_PhysicalLocation.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_PatronType.php';
class AnalyticsReport extends Report{
	function setupFilters(){
		global $interface;
		global $analytics;

		//Load session based filters
		$filters = array();
		$filters['countryId'] = $this->getSessionFilterSubTable(new Analytics_Country(), 'Country', 'countryId');
		$filters['cityId'] = $this->getSessionFilterSubTable(new Analytics_City(), 'City', 'cityId');
		$filters['stateId'] = $this->getSessionFilterSubTable(new Analytics_State(), 'State', 'stateId');
		$filters['themeId'] = $this->getSessionFilterSubTable(new Analytics_Theme(), 'Theme', 'themeId');
		$filters['mobile'] = $this->getSessionFilter('Mobile', 'mobile');
		$filters['deviceId'] = $this->getSessionFilterSubTable(new Analytics_Device(), 'Device', 'deviceId');
		$filters['physicalLocationId'] = $this->getSessionFilterSubTable(new Analytics_PhysicalLocation(), 'Physical Location', 'physicalLocationId');
		$filters['patronTypeId'] = $this->getSessionFilterSubTable(new Analytics_PatronType(), 'Patron Type', 'patronTypeId');
		$filters['homeLocationId'] = $this->getSessionFilter('Home Location', 'homeLocationId');
		$interface->assign('filters', $filters);

		$activeFilters = array();
		if (isset($_REQUEST['filter'])){
			foreach ($_REQUEST['filter'] as $index => $filterName){
				if (isset($_REQUEST['filterValue'][$index])){
					$filterVal = $_REQUEST['filterValue'][$index];
					$activeFilters[$index] = array(
						'name' => $filterName,
						'value' => $filterVal
					);
				}
			}
		}

		//Load date based filters
		if (isset($_REQUEST['startDate'])){
			$startDate = DateTime::createFromFormat('m-d-Y', $_REQUEST['startDate']);
		}else{
			$startDate = new DateTime();
			$startDate->modify('-1 month');
		}
		if (isset($_REQUEST['endDate'])){
			$endDate = DateTime::createFromFormat('m-d-Y', $_REQUEST['endDate']);
		}else{
			$endDate = new DateTime();
		}
		if ($endDate->getTimestamp() < $startDate->getTimestamp()){
			$tempDate = $startDate;
			$startDate = $endDate;
			$endDate = $tempDate;
		}
		$interface->assign('startDate', $startDate);
		$interface->assign('endDate', $endDate);

		$interface->assign('activeFilters', $activeFilters);
		$interface->assign('filterString', $analytics->getSessionFilterString());
	}

	/**
	 * @param DB_DataObject $class
	 * @param string $label
	 * @param string $field
	 * @return array
	 */
	function getSessionFilterSubTable($class, $label, $field){
		$class->orderBy('value ASC');
		$class->find();
		$filter = array();
		$filter['label'] = $label;
		$filter['field'] = $field;
		$filter['values'] = array();
		$filter['values']['null'] = 'unset';
		while ($class->fetch()){
			$filter['values'][$class->id] = $class->value;
		}
		natcasesort($filter['values']);
		return $filter;
	}

	function getSessionFilter($label, $field){
		$analyticsSession = new Analytics_Session();
		$analyticsSession->selectAdd();
		$analyticsSession->selectAdd("distinct($field)");
		$analyticsSession->find();

		$filter = array();
		$filter['label'] = $label;
		$filter['field'] = $field;
		while ($analyticsSession->fetch()){
			if ($analyticsSession->$field == null){
				$filter['values']['null'] = 'unset';
			}else{
				$filter['values'][$analyticsSession->$field] = $analyticsSession->$field;
			}
 		}
 		natcasesort($filter['values']);
 		return $filter;
 	}
}