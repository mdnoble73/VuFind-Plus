<?php
/**
 * OAI Server class for Authority core
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
 * @package  OAI_Server
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/tracking_record_changes Wiki
 */
require_once 'services/OAI/lib/OAIServer.php';

/**
 * OAI Server class for Authority core
 *
 * This class provides OAI server functionality.
 *
 * @category VuFind
 * @package  OAI_Server
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/tracking_record_changes Wiki
 */
class OAIServer_Auth extends OAIServer
{
    /**
     * Constructor
     *
     * @param string $baseURL The base URL for the OAI server
     * @param array  $params  The incoming OAI-PMH parameters (i.e. $_GET)
     *
     * @access public
     */
    public function __construct($baseURL, $params)
    {
        parent::__construct($baseURL, $params);
        $this->core = 'authority';
    }

    /**
     * Initialize the index engine for searches.  (This is called by the constructor
     * and is only a separate method to allow easy override by child classes).
     *
     * @return void
     * @access protected
     */
    protected function initializeIndex()
    {
        global $configArray;

        $this->index = ConnectionManager::connectToIndex('SolrAuth');
    }

    /**
     * Load data from the OAI section of config.ini.  (This is called by the
     * constructor and is only a separate method to allow easy override by child
     * classes).
     *
     * @return void
     * @access protected
     */
    protected function initializeSettings()
    {
        global $configArray;

        // Use some of the same settings as the regular OAI server, but override
        // others:
        parent::initializeSettings();
        $this->repositoryName = 'Authority Data Store';
        $this->setField = 'source';
    }

    /**
     * Get an array of information on non-deleted records in the specified range.
     *
     * @param int    $from   Start date.
     * @param int    $until  End date.
     * @param int    $offset First record to obtain in full detail.
     * @param int    $limit  Max number of full records to return.
     * @param string $set    Set to limit to (empty string for none).
     *
     * @return array         Solr response.
     * @access protected
     */
    protected function listRecordsGetNonDeleted($from, $until, $offset, $limit,
        $set = ''
    ) {
        // Load the result in the usual fashion, but tweak the results:
        $response = parent::listRecordsGetNonDeleted(
            $from, $until, $offset, $limit, $set
        );
        if (isset($response['response']['docs'])
            && is_array($response['response']['docs'])
        ) {
            for ($x = 0; $x < count($response['response']['docs']); $x++) {
                $response['response']['docs'][$x]
                    = $this->_tweakRecord($response['response']['docs'][$x]);
            }
        }
        return $response;
    }

    /**
     * Load a specific record from the index.
     *
     * @param string $id The record ID to load
     *
     * @return mixed     The record array (if successful) or false
     * @access protected
     */
    protected function loadRecord($id)
    {
        // Load the record in the usual fashion, but tweak it as needed:
        $record = parent::loadRecord($id);
        return is_array($record) ? $this->_tweakRecord($record) : $record;
    }

    /**
     * Tweak an authority record from Solr so it loads correctly.
     *
     * @param array $record The record to adjust.
     *
     * @return array        The adjusted record.
     * @access private
     */
    private function _tweakRecord($record)
    {
        // Adjust the record so it gets loaded using the MARC record driver and
        // has a title for use in OAI-DC mode:
        $record['recordtype'] = 'marc';
        $record['title'] = $record['heading'];
        return $record;
    }
}
?>