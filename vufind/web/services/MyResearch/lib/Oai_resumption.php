<?php
/**
 * Table Definition for oai_resumption
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
 * @package  DB_DataObject
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://pear.php.net/package/DB_DataObject/ PEAR Documentation
 */
require_once 'DB/DataObject.php';

/**
 * Table Definition for oai_resumption
 *
 * @category VuFind
 * @package  DB_DataObject
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://pear.php.net/package/DB_DataObject/ PEAR Documentation
 */ // @codingStandardsIgnoreStart
class Oai_resumption extends DB_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'oai_resumption';                  // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $params;                          // blob(65535)  blob
    public $expires;                         // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Oai_resumption',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    // @codingStandardsIgnoreEnd

    /**
     * Remove all expired tokens from the database.
     *
     * @return void
     * @access public
     */
    public function removeExpired()
    {
        $cleaner = new Oai_resumption();
        $now = date('Y-m-d H:i:s');
        $cleaner->whereAdd("\"expires\" <= '$now'");
        $cleaner->delete(true);
    }

    /**
     * Extract an array of parameters from the object.
     *
     * @return array Original saved parameters.
     * @access public
     */
    public function restoreParams()
    {
        $parts = explode('&', $this->params);
        $params = array();
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part);
            $key = urldecode($key);
            $value = urldecode($value);
            $params[$key] = $value;
        }
        return $params;
    }

    /**
     * Encode an array of parameters into the object.
     *
     * @param array $params Parameters to save.
     *
     * @return void
     * @access public
     */
    public function saveParams($params)
    {
        ksort($params);
        $processedParams = array();
        foreach ($params as $key => $value) {
            $processedParams[] = urlencode($key) . '=' . urlencode($value);
        }
        $this->params = implode('&', $processedParams);
    }
}
