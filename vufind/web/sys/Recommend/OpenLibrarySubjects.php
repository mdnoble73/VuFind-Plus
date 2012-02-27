<?php
/**
 * OpenLibrarySubjects Recommendations Module
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
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Eoghan Ó Carragáin <eoghan.ocarragain@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_recommendations_module Wiki
 */
require_once 'sys/OpenLibraryUtils.php';
require_once 'sys/Recommend/Interface.php';

/**
 * OpenLibrarySubjects Recommendations Module
 *
 * This class provides recommendations by doing a search of the catalog; useful
 * for displaying catalog recommendations in other modules (i.e. Summon, Web, etc.)
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Eoghan Ó Carragáin <eoghan.ocarragain@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_recommendations_module Wiki
 */
class OpenLibrarySubjects implements RecommendationInterface
{
    private $_searchObject;
    private $_requestParam;
    private $_limit;
    private $_pubFilter;
    private $_publishedIn;
    private $_subjectTypes;

    /**
     * Constructor
     *
     * Establishes base settings for making recommendations.
     *
     * @param object $searchObject The SearchObject requesting recommendations.
     * @param string $params       Colon-separated settings from config file.
     *
     * @access public
     */
    public function __construct($searchObject, $params)
    {
        $this->_searchObject = $searchObject;
        // Parse out parameters:
        $params = explode(':', $params);
        $this->_requestParam = empty($params[0]) ? 'lookfor' : $params[0];
        $this->_limit = isset($params[1]) && is_numeric($params[1]) && $params[1] > 0
            ? intval($params[1]) : 5;
        $this->_pubFilter = (!isset($params[2]) || empty($params[2])) ?
            'publishDate' : $params[2];
        if (strtolower(trim($this->_pubFilter)) == 'false') {
            $this->_pubFilter = false;
        }

        if (isset($params[3])) {
            $this->_subjectTypes = explode(',', $params[3]);
        } else {
            $this->_subjectTypes = array("topic");
        }

        // A 4th parameter is not specified in searches.ini, if it exists
        //     it has been passed in by an AJAX call and carries the
        //     publication date range in the form YYYY-YYYY
        if (isset($params[4]) && strstr($params[4], '-') != false) {
            $this->_publishedIn = $params[4];
        }
    }

    /**
     * init
     *
     * Called before the SearchObject performs its main search.  This may be used
     * to set SearchObject parameters in order to generate recommendations as part
     * of the search.
     *
     * @return void
     * @access public
     */
    public function init()
    {
        // No action needed here.
    }

    /**
     * process
     *
     * Called after the SearchObject has performed its main search.  This may be
     * used to extract necessary information from the SearchObject or to perform
     * completely unrelated processing.
     *
     * @return void
     * @access public
     */
    public function process()
    {
        global $interface;

        // Get and normalise $requestParam
        $subject =  $_REQUEST[$this->_requestParam];

        // Only proceed if we havea request paramater value
        if (!empty($subject)) {
            // If publication date filter is enabled check for parameters
            $published_in = '';
            if ($this->_pubFilter !== false) {
                // If parameters have been passed from AJAX, use them
                $published_in = !empty($this->_publishedIn)
                    ? $this->_publishedIn
                    : $this->getPublishedDates($this->_pubFilter);
            }

            $result = array();
            $ol = New OpenLibraryUtils();
            $result = $ol->getSubjects(
                $subject, $published_in, $this->_subjectTypes, true, false,
                $this->_limit, null, true
            );

            if (!empty($result)) {
                $interface->assign('validData', true);
                $interface->assign('worksArray', $result);
                $interface->assign('subject', $subject);
            }
        }
    }


    /**
     * Support function to get publication date range. Return string in the form
     * "YYYY-YYYY"
     *
     * @param string $field Name of filter field to check for date limits.
     *
     * @return string
     * @access protected
     */
    protected function getPublishedDates($field)
    {
        // Try to extract range details from request parameters or SearchObject:
        if (isset($_REQUEST[$field . 'from'])
            && isset($_REQUEST[$field . 'to'])
        ) {
            $range = array(
                'from' => $_REQUEST[$field . 'from'],
                'to' => $_REQUEST[$field . 'to']
            );
        } else if (is_object($this->_searchObject)) {
            $currentFilters = $this->_searchObject->getFilters();
            if (isset($currentFilters[$field][0])) {
                $range = VuFindSolrUtils::parseRange($currentFilters[$field][0]);
            }
        }

        // Normalize range if we found one:
        if (isset($range)) {
            if (empty($range['from']) || $range['from'] == '*') {
                $range['from'] = 0;
            }
            if (empty($range['to']) || $range['to'] == '*') {
                $range['to'] = date('Y') + 1;
            }
            return $range['from'] . '-' . $range['to'];
        }

        // No range found?  Return empty string:
        return '';
    }

    /**
     * getTemplate
     *
     * This method provides a template name so that recommendations can be displayed
     * to the end user.  It is the responsibility of the process() method to
     * populate all necessary template variables.
     *
     * @return string The template to use to display the recommendations.
     * @access public
     */
    public function getTemplate()
    {
        return 'Search/Recommend/OpenLibrarySubjects.tpl';
    }
}

?>