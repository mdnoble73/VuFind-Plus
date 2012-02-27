<?php
/**
 * Authority Record Server action for OAI module
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
 * @package  Controller_OAI
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
require_once 'Action.php';
require_once 'services/OAI/lib/OAIServer_Auth.php';

/**
 * Authority Record Server action for OAI module
 *
 * @category VuFind
 * @package  Controller_OAI
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class AuthServer extends Action
{
    /**
     * Process parameters and display the page.
     *
     * @return void
     * @access public
     */
    public function launch()
    {
        global $configArray;

        // Collect relevant parameters for OAI server:
        $baseURL = $configArray['Site']['url'] . '/OAI/AuthServer';
        $params = empty($_GET) ? $_POST : $_GET;

        // Don't pass VuFind-specific parameters down to OAI server:
        unset($params['module']);
        unset($params['action']);

        // Respond to the OAI request:
        $server = new OAIServer_Auth($baseURL, $params);
        $server->respond();
    }
}
?>