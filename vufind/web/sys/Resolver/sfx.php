<?php
/**
 * SFX Link Resolver Driver
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
require_once 'Interface.php';

/**
 * SFX Link Resolver Driver
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_link_resolver_driver Wiki
 */
class Resolver_Sfx implements ResolverInterface
{
    private $_baseUrl;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        // Load Configuration for this Module
        global $configArray;
        $this->_baseUrl = $configArray['OpenURL']['url'];
    }

    /**
     * Fetch Links
     *
     * Fetches a set of links corresponding to an OpenURL
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string         raw XML returned by resolver
     * @access public
     */
    public function fetchLinks($openURL)
    {
        // Make the call to SFX and load results
        $url = $this->_baseUrl . 
            '?sfx.response_type=multi_obj_detailed_xml&svc.fulltext=yes&' . $openURL;
        $feed = file_get_contents($url);
        return $feed;
    }

    /**
     * Parse Links
     *
     * Parses an XML file returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $xmlstr Raw XML returned by resolver
     *
     * @return array         Array of values
     * @access public
     */
    public function parseLinks($xmlstr)
    {
        $records = array(); // array to return
        try {
            $xml = new SimpleXmlElement($xmlstr);
        } catch (Exception $e) {
            return $records;
        }

        $root = $xml->xpath("//ctx_obj_targets");
        $xml = $root[0];
        foreach ($xml->children() as $target) {
            $record = array();
            $record['title'] = (string)$target->target_public_name;
            $record['href'] = (string)$target->target_url;
            $record['service_type'] = (string)$target->service_type;
            $record['coverage'] = (string)$target->coverage->coverage_text
                ->threshold_text->coverage_statement;
            array_push($records, $record);
        }
        return $records;
    }
}

?>
