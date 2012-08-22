<?php
/**
 * Results action for AlphaBrowse module
 *
 * PHP version 5
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
 * @category VuFind
 * @package  Controller_AlphaBrowse
 * @author   Mark Triggs <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/alphabetical_heading_browse Wiki
 */
require_once 'Home.php';

/**
 * Results action for AlphaBrowse module
 *
 * @category VuFind
 * @package  Controller_AlphaBrowse
 * @author   Mark Triggs <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/alphabetical_heading_browse Wiki
 */
class Results extends Home {
	/**
	 * Display the page.
	 *
	 * @return void
	 * @access public
	 */
	public function launch() {
		global $interface;
		global $configArray;

		// Connect to Solr:
		$db = ConnectionManager::connectToIndex();

		// Process incoming parameters:
		$source = isset($_GET['source']) ? $_GET['source'] : false;
		$type = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : $_REQUEST['type'];
		
		if ($source == false && $type){
			$source = $type;
			if (strpos($source, 'browse') === 0){
				$source = substr($source, strlen('browse'));
				$source = strtolower( substr($source,0,1) ) . substr($source,1);
			}
		}
		$interface->assign('searchIndex', 'browse' . ucfirst($source));
		$from = isset($_GET['from']) ? $_GET['from'] : false;
		if ($from == false & isset($_REQUEST['lookfor'])){
			$from = $_REQUEST['lookfor'];
		}
		$interface->assign('lookfor', $from);
		$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? $_GET['page'] : 0;
		$limit = isset($configArray['AlphaBrowse']['page_size']) ? $configArray['AlphaBrowse']['page_size'] : 20;

		// If required parameters are present, load results:
		if ($source && $from !== false) {
			require_once('sys/AlphaBrowse.php');
			$alphaBrowse = new AlphaBrowse();
			$result = $alphaBrowse->getBrowseResults($source, $from, $page, $limit);
			
			// No results?  Try the previous page just in case we've gone past the
			// end of the list....
			if (!$result['success']) {
				$page--;
				$result = $alphaBrowse->getBrowseResults($source, $from, $page, $limit);
			}

			if ($result['totalCount'] == 0){
				$interface->assign('error', "No Results were found");
			}else{
				// Only display next/previous page links when applicable:
				if ($result['showNext']) {
					$interface->assign('nextpage', $page + 1);
				}
				if ($result['showPrev']) {
					$interface->assign('prevpage', $page - 1);
				}
	
				// Send other relevant values to the template:
				$interface->assign('source', $source);
				$interface->assign('from', $from);
				$interface->assign('result', $result);
			}
		}

		// We also need to load all the same details as the basic Home action:
		parent::launch();
	}

}