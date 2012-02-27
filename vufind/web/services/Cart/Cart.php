<?php
/**
 * Base class shared by most Cart module actions.
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
require_once 'sys/Cart_Model.php';


/**
 * Base class shared by most Cart module actions.
 *
 * @category VuFind
 * @package  Controller_Cart
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Cart extends Action
{
	protected $cart;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct()
	{
		parent::__construct();
		$this->cart = Cart_Model::getInstance();
	}

	/**
	 * Process parameters and display cart contents.
	 *
	 * @return void
	 * @access public
	 */
	public function viewCart()
	{
		global $interface;
		$interface->assign('cart', $this->getCartAsHTML());
		$interface->assign('isEmpty', $this->cart->isEmpty());
		$interface->setTemplate('view.tpl');
		$interface->setPageTitle('Book Bag');
		$interface->display('layout.tpl');
	}

	/**
	 * Process parameters and return the cart content as HTML.
	 *
	 * @return string the cart content formatted as HTML
	 * @access public
	 */
	public function getCartAsHTML()
	{
		global $interface;

		// Setup Search Engine Connection
		$db = ConnectionManager::connectToIndex();

		// fetch records from search engine
		// FIXME: currently only work with VuFind records
		// we should make this work with Summon/WorldCat too
		$records = array();
		$items = $this->cart->getItems();
		foreach ($items as $item) {
			if ($record = $db->getRecord($item)) {
				// TODO: perhaps we could use RecordDriver here
				$records[] = $record;
			}
		}
		$interface->assign('records', $records);
		return $interface->fetch('Cart/cart.tpl');
	}
}
