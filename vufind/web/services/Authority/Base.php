<?php
/**
 * Base Class for Authority module
 *
 * PHP version 5
 *
 * Copyright (C) Andrew Nagy 2009.
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
 * @package  Controller_Authority
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
require_once 'Action.php';

/**
 * Base Class for Authority module
 *
 * @category VuFind
 * @package  Controller_Authority
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Base extends Action
{
    protected $searchObject;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        global $interface;
        $interface->assign('currentTab', 'Authority');

        // Send Authority search types to the template so the basic search box can
        // function on all pages of the Authority UI.
        $this->searchObject = SearchObjectFactory::initSearchObject('SolrAuth');
        $interface->assign('authSearchTypes', $this->searchObject->getBasicTypes());
    }
}
?>
