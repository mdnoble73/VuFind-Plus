<?php
require_once 'services/Report/Report.php';
class AnalyticsReport extends Report{
	function setupFilters(){
		global $interface;
		global $user;
		global $analytics;

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

		$filterParams = "";
		$activeFilters = array();
		if (isset($_REQUEST['filter'])){
			foreach ($_REQUEST['filter'] as $index => $filterName){
				if (isset($_REQUEST['filterValue'][$index])){
					$filterVal = $_REQUEST['filterValue'][$index];
					$filterParams .= "&filter[$index]={$filterName}";
					$filterParams .= "&filterValue[$index]={$filterVal}";
					$activeFilters[$index] = array(
						'name' => $filterName,
						'value' => $filterVal
					);
				}
			}
		}
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