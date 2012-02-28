<?php
/**
 * Home action for AlphaBrowse module
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
require_once 'Action.php';

/**
 * Home action for AlphaBrowse module
 *
 * @category VuFind
 * @package  Controller_AlphaBrowse
 * @author   Mark Triggs <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/alphabetical_heading_browse Wiki
 */
class Home extends Action{
	/**
	 * Display the page.
	 *
	 * @return void
	 * @access public
	 */
	public function launch(){
		global $interface;
		global $configArray;

		// Load browse types from config file, or use defaults if unavailable:
		if (isset($configArray['AlphaBrowse_Types']) && is_array($configArray['AlphaBrowse_Types'])) {
			$types = $configArray['AlphaBrowse_Types'];
		} else {
			$types = array(
                'topic' => 'By Topic',
                'author' => 'By Author',
                'title' => 'By Title',
                'dewey' => 'By Call Number'
                );
		}
		$interface->assign('alphaBrowseTypes', $types);

		// Display the page:
		$interface->setPageTitle('Browse the Collection Alphabetically');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl');
	}
}