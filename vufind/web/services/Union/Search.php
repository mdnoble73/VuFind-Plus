<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

/**
 * Union Results
 * Provides a way of unifying searching disparate sources either by
 * providing joined results between the sources or by including results from
 * a single source
 *
 * @author Mark Noble
 *
 */
class Search extends Action {
	function launch(){
		//Get the search source and determine what to show.
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		//Check the search source
		$searchSources = new SearchSources();
		$searches = $searchSources->getSearchSources();
		$searchInfo = $searches[$searchSource];
		global $module;
		global $action;
		global $interface;
		if (isset($searchInfo['external']) && $searchInfo['external'] == true){
			//Reset to a local search source so the external search isn't remembered
			$_SESSION['searchSource'] = 'local';
			//Need to redirect to the appropriate search location with the new value for look for
			$type = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : $_REQUEST['type'];
			$lookfor = isset($_REQUEST['lookfor']) ? $_REQUEST['lookfor'] : '';
			$filters = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : null;
			$link = $searchSources->getExternalLink($searchSource, $type, $lookfor);
			header('Location: ' . $link);
			die();
		}else if ($searchSource == 'genealogy'){
			require_once ('services/Genealogy/Results.php');
			$module = 'Search';
			$interface->assign('module', $module);
			$action = 'Results';
			$interface->assign('action', $action);
			$results = new Results();
			return $results->launch();
		}else{
			$type = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : $_REQUEST['type'];
			if (strpos($type , 'browse') === 0){
				require_once ('services/AlphaBrowse/Results.php');
				$module = 'AlphaBrowse';
				$interface->assign('module', $module);
				$action = 'Results';
				$interface->assign('action', $action);
				$results = new Results();
				return $results->launch();
			}else{
				require_once ('services/Search/Results.php');
				$module = 'Search';
				$interface->assign('module', $module);
				$action = 'Results';
				$interface->assign('action', $action);
				$results = new Results();
				return $results->launch();
			}
		}
	}
}