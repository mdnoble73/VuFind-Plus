<?php
/**
 * Common AJAX functions for the Browse module using JSON as output format.
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
 * @package  Controller_AJAX
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */

require_once 'JSON.php';

/**
 * Common AJAX functions for the Browse module using JSON as output format.
 *
 * @category VuFind
 * @package  Controller_AJAX
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class JSON_Browse extends JSON
{
    private $_searchObject;

    /**
     * Constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        $this->_searchObject = SearchObjectFactory::initSearchObject();
    }

    /**
     * Get the browse options formated as HTML, and send output as a JSON object.
     *
     * @return void
     * @access public
     */
    public function getOptionsAsHTML()
    {
        global $interface;
        
        if (isset($_GET['next_query_field']) && !empty($_GET['next_query_field'])) {
            $interface->assign('next_query_field', $_GET['next_query_field']);
            $interface->assign('next_facet_field', $_GET['next_facet_field']);
            $interface->assign('next_target', $_GET['next_target']);
        }
        $this->output($this->_processSearch('Browse/options.tpl'), JSON::STATUS_OK);
    }

    /**
     * Get the subject browse options formated as HTML, and send output as a JSON
     * object.
     *
     * @return void
     * @access public
     */
    public function getSubjectsAsHTML()
    {
        $_GET['query'] = '[* TO *]';
        $this->output(
            $this->_processSearch('Browse/subjects.tpl'), JSON::STATUS_OK
        );
    }

    /**
     * Get the alphabet browse options formated as HTML, and send output as a JSON
     * object.
     *
     * @return void
     * @access public
     */
    public function getAlphabetAsHTML()
    {
        global $interface;

        $letters = array();
        if ($_GET['include_numbers']) {
            $letters = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        }
        for ($i=0; $i<26; $i++) {
            $letters[] = chr(65 + $i);
        }
        $interface->assign('letters', $letters);
        $interface->assign('query_field', $_GET['query_field']);
        $interface->assign('facet_field', $_GET['facet_field']);
        $this->output($interface->fetch('Browse/alphabet.tpl'), JSON::STATUS_OK);
    }

    /**
     * Process parameters and return options as HTML string.
     *
     * @param string $template the smarty template file for formatting the results.
     *
     * @return string
     * @access private
     */
    private function _processSearch($template)
    {
        global $interface;

        $this->_searchObject->initBrowseScreen();
        $this->_searchObject->disableLogging();

        if (isset($_GET['facet_field'])) {
            $this->_searchObject->addFacet($_GET['facet_field']);
        }
        if (isset($_GET['facet_prefix'])) {
            $this->_searchObject->addFacetPrefix($_GET['facet_prefix']);
        }
        $query = (isset($_GET['query']) && !empty($_GET['query']))
            ? $_GET['query'] : '*:*';
        if (isset($_GET['query_field']) && !empty($_GET['query_field'])) {
            $query = $_GET['query_field'] . ':' . $query;
        }
        $this->_searchObject->setQueryString($query);
        $result = $this->_searchObject->processSearch();
        $this->_searchObject->close();
        
        $facets = $result['facet_counts']['facet_fields'][$_GET['facet_field']];
        $interface->assign('facets', $facets);
        $interface->assign('query_field', $_GET['query_field']);
        $interface->assign('facet_field', $_GET['facet_field']);
        $interface->assign('query', $query);

        return $interface->fetch($template);
    }
}
?>
