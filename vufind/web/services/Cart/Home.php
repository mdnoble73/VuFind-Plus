<?php
/**
 * Home action for Cart module
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
 * @package  Controller_Cart
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */

require_once 'Action.php';
require_once 'Cart.php';

/**
 * Home action for Cart module
 *
 * @category VuFind
 * @package  Controller_Cart
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Home extends Cart
{
	/**
	 * Process parameters and display the page.
	 *
	 * @return void
	 * @access public
	 */
	public function launch()
	{
		global $interface;

		if (isset($_REQUEST['empty'])) {
			$this->cart->emptyCart();
		} else if (isset($_REQUEST['add'])) {
			$this->cart->addItems($_REQUEST['id']);
		} else if (isset($_REQUEST['delete'])) {
			$this->cart->removeItem($_REQUEST['delete']);
		}

		$this->viewCart();
	}
}
