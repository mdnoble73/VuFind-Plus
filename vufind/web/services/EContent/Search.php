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

require_once 'Action.php';
//require_once 'sys/DataObjects/Pillar.php';
require_once('services/Admin/Admin.php');
require_once('sys/eContent/EContentRecord.php');
require_once('sys/eContent/EContentItem.php');
require_once 'sys/DataObjectUtil.php';
require_once 'sys/Pager.php';

class Search extends Admin {

	function launch()
	{
		global $interface;
		global $configArray;

		$results = array();
		
		$epubFile = new EContentItem();
		
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$recordsPerPage = 25;
		$searchUrl = $configArray['Site']['path'] . '/EContent/Search';
		$searchParams = array();
		foreach ($_REQUEST as $key=>$value){
			if (!in_array($key, array('module', 'action', 'page'))){
				$searchParams[] = "$key=$value";
			}
		}
		$searchUrl = $searchUrl . '?page=%d&' . implode('&', $searchParams);
		$interface->assign('page', $currentPage);

		$epubFile = new EContentRecord();
		if (isset($_REQUEST['sortOptions'])){
			$epubFile->orderBy($_REQUEST['sortOptions']);
			$interface->assign('sort', $_REQUEST['sortOptions']);
		}
    $numTotalFiles = $epubFile->count();
		$epubFile->limit(($currentPage - 1) * $recordsPerPage, 20);
		$epubFile->find();
		if ($epubFile->N > 0){
			while ($epubFile->fetch()){
				$results[] = clone $epubFile;
			}
		}
		$interface->assign('results', $results);

		$options = array('totalItems' => $numTotalFiles,
                     'fileName'   => $searchUrl,
                     'perPage'    => $recordsPerPage);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$interface->setTemplate('search.tpl');

		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('epubAdmin');
	}
}
