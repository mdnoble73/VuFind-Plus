<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/SolrStats.php';

class GroupedWork_SimilarTitles extends Action
{
	/** @var  Solr */
	private $db;
	function launch()
	{
		global $interface;
		global $user;
		global $configArray;
		global $logger;

		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		if (!$recordDriver->isValid){
			$logger->log("Did not find a record for id {$id} in solr." , PEAR_LOG_DEBUG);
			$interface->setTemplate('invalidRecord.tpl');
			$interface->display('layout.tpl');
			die();
		}

		$interface->assign('recordDriver', $recordDriver);

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);
		$this->db->disableScoping();
		$similar = $this->db->getMoreLikeThis2($id);
		// Send the similar items to the template; if there is only one, we need
		// to force it to be an array or things will not display correctly.
		if (isset($similar) && count($similar['response']['docs']) > 0) {
			$this->similarTitles = $similar['response']['docs'];
		}else{
			$this->similarTitles = array();
		}

		$resourceList = array();
		$curIndex = 0;
		if (isset($this->similarTitles) && is_array($this->similarTitles)){
			foreach ($this->similarTitles as $title){
				$interface->assign('resultIndex', ++$curIndex);
				$record = RecordDriverFactory::initRecordDriver($title);
				$resourceList[] = $interface->fetch($record->getSearchResult($user, null, false));
			}
		}
		$interface->assign('recordSet', $this->similarTitles);
		$interface->assign('resourceList', $resourceList);

		$interface->assign('recordStart', 1);
		$interface->assign('recordEnd', count($resourceList));
		$interface->assign('recordCount', count($resourceList));

		$novelist = NovelistFactory::getNovelist();
		$enrichment = $novelist->loadEnrichment($id, $recordDriver->getISBNs());
		$interface->assign('enrichment', $enrichment);

		$interface->assign('id', $id);

		//Build the actual view
		$interface->setTemplate('view-similar.tpl');

		$interface->setPageTitle('Similar to ' . $recordDriver->getTitle());

		// Display Page
		$interface->display('layout.tpl');
	}

}