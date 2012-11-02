<?php
require_once 'services/Report/Report.php';
class AnalyticsReport extends Report{
	function setupFilters(){
		global $interface;
		global $user;

		$filters = array();
		$filters[] = $this->getSessionFilter('Country', 'country');
		$filters[] = $this->getSessionFilter('City', 'city');
		$filters[] = $this->getSessionFilter('State', 'state');
		$filters[] = $this->getSessionFilter('Theme', 'theme');
		$filters[] = $this->getSessionFilter('Mobile', 'mobile');
		$filters[] = $this->getSessionFilter('Device', 'device');
		$filters[] = $this->getSessionFilter('Physical Location', 'physicalLocation');
		$filters[] = $this->getSessionFilter('Patron Type', 'patronType');
		$filters[] = $this->getSessionFilter('Home Location', 'homeLocationId');

		$interface->assign('filters', $filters);
	}

	function getSessionFilter($label, $field){
		$analyticsSession = new Analytics_Session();
		$analyticsSession->selectAdd();
		$analyticsSession->selectAdd("distinct($field)");
		$analyticsSession->find();
		$filter = array();
		$filter['label'] = $label;
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