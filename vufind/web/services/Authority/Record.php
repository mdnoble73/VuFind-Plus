<?php
/**
 * Record action for Authority module
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
 * @package  Controller_Authority
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
require_once 'Base.php';

/**
 * Record action for Authority module
 *
 * @category VuFind
 * @package  Controller_Authority
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Record extends Base
{
    protected $recordDriver;
    protected $cacheId;
    protected $db;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        global $interface;

        $interface->assign('id', $_GET['id']);

        // Setup Search Engine Connection
        $this->db = ConnectionManager::connectToIndex('SolrAuth');

        parent::__construct();
    }

    /**
     * Process parameters and display the page.
     *
     * @return void
     * @access public
     */
    public function launch()
    {
        global $interface;

        // Retrieve the record from the index
        if (!($record = $this->db->getRecord($_GET['id']))) {
            PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
        }

        // Send basic information to the template.
        $interface->setPageTitle(
            isset($record['heading']) ? $record['heading'] : 'Heading unavailable.'
        );
        $interface->assign('record', $record);

        // Load MARC data
        $marc = trim($record['fullrecord']);
        $marc = preg_replace('/#31;/', "\x1F", $marc);
        $marc = preg_replace('/#30;/', "\x1E", $marc);
        $marc = new File_MARC($marc, File_MARC::SOURCE_STRING);
        $marcRecord = $marc->next();
        if (!$marcRecord) {
            PEAR::raiseError(new PEAR_Error('Cannot Process MARC Record'));
        }
        $xml = trim($marcRecord->toXML());

        // Transform MARCXML
        $style = new DOMDocument;
        $style->load('services/Record/xsl/record-marc.xsl');
        $xsl = new XSLTProcessor();
        $xsl->importStyleSheet($style);
        $doc = new DOMDocument;
        if ($doc->loadXML($xml)) {
            $html = $xsl->transformToXML($doc);
            $interface->assign('details', $html);
        }

        // Assign the ID of the last search so the user can return to it.
        $interface->assign(
            'lastsearch',
            isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false
        );

        // Display Page
        $interface->setTemplate('record.tpl');
        $interface->display('layout.tpl');
    }
}

?>
