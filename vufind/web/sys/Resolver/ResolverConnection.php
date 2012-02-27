<?php
/**
 * Link Resolver Driver Wrapper
 *
 * PHP version 5
 *
 * Copyright (C) Royal Holloway, University of London
 *
 * last update: 2010-10-11
 * tested with X-Server SFX 3.2
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
 * @package  Resolver_Drivers
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_link_resolver_driver Wiki
 */

/**
 * Resolver Connection Class
 *
 * This abstract class defines the signature for the available methods for
 * interacting with the local OpenURL Resolver. It is a cutdown version
 * of the CatalogConnection class.
 *
 * Required functions in implementing Drivers are listed in Interface.php
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_link_resolver_driver Wiki
 */
class ResolverConnection
{
    /**
     * A boolean value that defines whether a connection has been successfully
     * made.
     *
     * @var    bool
     * @access public
     */
    public $status = false;

    /**
     * The object of the appropriate driver.
     *
     * @var    object
     * @access private
     */
    private $_driver = false;

    /**
     * A boolean value that defines whether to cache the resolver results
     *
     * @var    bool
     * @access private
     */
    private $_useCache = false;

    /**
     * The path to the resolver cache, if any
     *
     * @var    string
     * @access private
     */
    private $_cachePath = 'interface/resolver_cache';

    /**
     * Constructor
     *
     * This is responsible for instantiating the driver that has been specified.
     *
     * @param string $driver The name of the driver to load.
     *
     * @access public
     */
    function __construct($driver)
    {
        global $configArray;
        $path = "{$configArray['Site']['local']}/sys/Resolver/{$driver}.php";
        if (is_readable($path)) {
            include_once $path;
            try {
                $class = 'Resolver_' . ucwords($driver);
                $this->_driver = new $class();
            } catch (Exception $e) {
                throw $e;
            }
            $this->status = true;
            if (isset($configArray['OpenURL']['resolver_cache'])
                && is_dir($configArray['OpenURL']['resolver_cache'])
                && is_writable($configArray['OpenURL']['resolver_cache'])
            ) {
                $this->_useCache = true;
                $this->_cachePath = $configArray['OpenURL']['resolver_cache'];
                if (!(substr($this->_cachePath, -1) == '/')) {
                    $this->_cachePath .= '/';
                }
            }
        }
    }

    /**
     * Check if driver loaded successfully.
     *
     * @return bool
     * @access public
     */
    public function driverLoaded()
    {
        return is_object($this->_driver);
    }

    /**
     * Fetch Links
     *
     * This is responsible for retrieving the valid links for a
     * particular OpenURL. The links may be cached or fetched remotely.
     *
     * If an error occurs, throw exception
     *
     * @param string $openURL The OpenURL to use
     *
     * @return array          An associative array with the following keys:
     * linktype, aval, href, coverage
     * @access public
     */
    function fetchLinks($openURL)
    {
        $cache_found = false;
        if ($this->_useCache) {
            $hashedURL = md5($openURL);
            try {
                if (file_exists($this->_cachePath . $hashedURL)) {
                    $links = file_get_contents($this->_cachePath . $hashedURL);
                } else {
                    $links = $this->_driver->fetchLinks($openURL);
                    $fp = fopen($this->_cachePath . $hashedURL, 'w');
                    fwrite($fp, $links);
                    fclose($fp);
                }
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            $links = $this->_driver->fetchLinks($openURL);
        }
        return $this->_driver->parseLinks($links);
    }

    /**
     * Default method -- pass along calls to the driver if available; return
     * false otherwise.  This allows custom functions to be implemented in
     * the driver without constant modification to the connection class.
     *
     * @param string $methodName The name of the called method.
     * @param array  $params     Array of passed parameters.
     *
     * @return mixed             Varies by method (false if undefined method)
     * @access public
     */
    public function __call($methodName, $params)
    {
        $method = array($this->_driver, $methodName);
        if (is_callable($method)) {
            return call_user_func_array($method, $params);
        }
        return false;
    }
}

?>
