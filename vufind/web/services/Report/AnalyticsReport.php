<?php
require_once ROOT_DIR . '/services/Report/Report.php';
class AnalyticsReport extends Report{
	function setupFilters(){
		global $interface;
		global $analytics;

		//Load session based filters
		$filters = array();
		$filters['country'] = $this->getSessionFilter('Country', 'country');
		$filters['city'] = $this->getSessionFilter('City', 'city');
		$filters['state'] = $this->getSessionFilter('State', 'state');
		$filters['theme'] = $this->getSessionFilter('Theme', 'theme');
		$filters['mobile'] = $this->getSessionFilter('Mobile', 'mobile');
		$filters['device'] = $this->getSessionFilter('Device', 'device');
		$filters['physicalLocation'] = $this->getSessionFilter('Physical Location', 'physicalLocation');
		$filters['patronType'] = $this->getSessionFilter('Patron Type', 'patronType');
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