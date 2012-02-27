<?php
/**
 * MultiAuth Authentication plugin
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Authentication
 * @author   Sam Moffatt <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_authentication_handler Wiki
 */
require_once 'PEAR.php';
require_once 'services/MyResearch/lib/User.php';
require_once 'Authentication.php';
require_once 'AuthenticationFactory.php';

/**
 * MultiAuth Authentication plugin
 *
 * This module enables chaining of multiple authentication plugins.  Authentication
 * plugins are executed in order, and the first successful authentication is
 * returned with the rest ignored.  The last error message is used to be returned
 * to the calling function.
 *
 * The plugin works by being defined as the authentication handler for the system
 * and then defining its own order for plugins.  For example, you could edit
 * config.ini like this:
 *
 * [Authentication]
 * method = MultiAuth
 *
 * [MultiAuth]
 * method_order = "ILS,LDAP"
 * filters = "username:strtoupper,username:trim,password:trim"
 *
 * This example uses a combination of ILS and LDAP authentication, checking the ILS
 * first and then failing over to LDAP.
 *
 * The filters follow the format fieldname:PHP string function, where fieldname is
 * either "username" or "password."  In the example, we uppercase the username and
 * trim the username and password fields. This is done to enable common filtering
 * before handing off to the authentication handlers.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Sam Moffatt <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_authentication_handler Wiki
 */
class MultiAuthAuthentication implements Authentication
{
    private $_filters = array();
    private $_methods;
    private $_username;
    private $_password;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        global $configArray;

        if (!isset($configArray['MultiAuth'])
            || !isset($configArray['MultiAuth']['method_order'])
            || !strlen($configArray['MultiAuth']['method_order'])
        ) {
            throw new InvalidArgumentException(
                "One or more MultiAuth parameters are missing. " .
                "Check your config.ini!"
            );
        }
        $this->_methods = explode(',', $configArray['MultiAuth']['method_order']);
        if (isset($configArray['MultiAuth']['filters'])
            && strlen($configArray['MultiAuth']['filters'])
        ) {
            $this->_filters = explode(',', $configArray['MultiAuth']['filters']);
        }
    }

    /**
     * Attempt to authenticate the current user.
     *
     * @return object User object if successful, PEAR_Error otherwise.
     * @access public
     */
    public function authenticate()
    {
        $this->_filterCredentials();

        // Check for empty credentials before we do any extra work:
        if ($this->_username == '' || $this->_password == '') {
            return new PEAR_Error('authentication_error_blank');
        }

        // Update $_POST with our filtered credentials:
        $_POST['username'] = $this->_username;
        $_POST['password'] = $this->_password;

        // Do the actual authentication work:
        return $this->_authUser();
    }

    /**
     * Load credentials into the object and apply internal filter settings to them.
     *
     * @return void
     * @access private
     */
    private function _filterCredentials()
    {
        $this->_username = $_POST['username'];
        $this->_password = $_POST['password'];

        foreach ($this->_filters as $filter) {
            $parts = explode(':', $filter);
            $property = '_' . trim($parts[0]);
            if (isset($this->$property)) {
                $this->$property = call_user_func(trim($parts[1]), $this->$property);
            }
        }
    }

    /**
     * Do the actual work of authenticating the user (support method for
     * authenticate()).
     *
     * @return object User object if successful, PEAR_Error otherwise.
     * @access private
     */
    private function _authUser()
    {
        // Try authentication methods until we find one that works:
        foreach ($this->_methods as $method) {
            $authenticator
                = AuthenticationFactory::initAuthentication(trim($method));
            $user = $authenticator->authenticate();
            if (!PEAR::isError($user)) {
                break;
            }
        }

        // At this point, there are three possibilities: $user is a valid,
        // logged-in user; $user is a PEAR_Error that we need to return; or
        // $user is undefined, indicating that $this->_methods is empty and
        // thus something is wrong!
        return isset($user)
            ? $user : new PEAR_Error('authentication_error_technical');
    }
}
?>