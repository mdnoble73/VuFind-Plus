<?php
/**
 * vuDL Record Driver
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
 * @package  RecordDrivers
 * @author   David Lacy <david.lacy@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/other_than_marc Wiki
 */
require_once 'RecordDrivers/IndexRecord.php';

/**
 * vuDL Record Driver
 *
 * This class is designed to handle vuDL records.  Much of its functionality
 * is inherited from the default index-based driver.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   David Lacy <david.lacy@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/other_than_marc Wiki
 */
class VudlRecord extends IndexRecord
{
    /**
     * Return a URL to a thumbnail preview of the record, if available; false
     * otherwise.
     *
     * @param array $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return mixed
     * @access protected
     */
    protected function getThumbnail($size = 'small')
    {
        // We are currently storing only one size of thumbnail; we'll use this for
        // small and medium sizes in the interface, flagging "large" as unavailable
        // for now.
        if ($size == 'large') {
            return false;
        }
        if (strlen($this->fields['fullrecord'])) {
            $xml = $this->fields['fullrecord'];
            $result = simplexml_load_string($xml);
        }
        $thumb = isset($result->thumbnail) ? trim($result->thumbnail) : false;
        return empty($thumb) ? false : $thumb;
    }

    /**
     * Return an associative array of URLs associated with this record (key = URL,
     * value = description).
     *
     * @return array
     * @access protected
     */
    protected function getURLs()
    {
        global $configArray;

        // VuDL records get displayed within a custom VuFind module -- let's just
        // link directly there:
        $url = $configArray['Site']['url'] . '/VuDL/Record?id=' .
            urlencode($this->getUniqueID());
        return array($url => $url);
    }
}

?>
