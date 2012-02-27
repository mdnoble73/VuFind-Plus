<?php
/**
 * OpenLibrarySubjectsDeferred Recommendations Module
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
require_once 'sys/Recommend/OpenLibrarySubjects.php';

/**
 * OpenLibrarySubjectsDeferred Recommendations Module
 *
 * This class sets up an AJAX call to trigger a call o the OpenLibrarySubjects
 * module.  It extends that class in order to share the getPublishedDates() utility
 * method.
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Eoghan Ó Carragáin <eoghan.ocarragain@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_recommendations_module Wiki
 */
class OpenLibrarySubjectsDeferred extends OpenLibrarySubjects
{
    private $_searchObject;
    private $_requestParam;
    private $_params;
    private $_lookfor;

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
        $paramsArray = explode(':', $params);
        $this->_requestParam = $paramsArray[0] = empty($paramsArray[0])
            ? 'lookfor' : $paramsArray[0];

        // Make sure all elements of the paramsArray are filled in, even if just
        // with a blank string, so we can rebuild the parameters to pass through
        // AJAX later on!
        $paramsArray[1] = isset($paramsArray[1]) ? $paramsArray[1] : '';

        // If Publication Date filter is to be applied, get the range and add it to
        //    $params since the $searchObject will not be available after the AJAX
        //    call
        if (!isset($paramsArray[2]) || empty($paramsArray[2])) {
            $paramsArray[2] = 'publishDate';
        }
        $pubDateRange = strtolower($paramsArray[2]) == 'false' ?
            array() : $this->getPublishedDates($paramsArray[2]);
        if (!empty($pubDateRange)) {
            // Check if [Subject types] parameter has been supplied in searches.ini
            if (!isset($paramsArray[3])) {
                $paramsArray[3] = '';
            }
            $paramsArray[4] = $pubDateRange;
        }

        // Collect the best possible search term(s):
        $this->_lookfor = isset($_REQUEST[$this->_requestParam])
            ? $_REQUEST[$this->_requestParam] : '';
        if (empty($this->_lookfor) && is_object($searchObject)) {
            $this->_lookfor = $searchObject->extractAdvancedTerms();
        }

        $this->_params = implode(':', $paramsArray);
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

        $interface->assign('deferredOLSubjectsParams', $this->_params);
        $interface->assign('deferredOLSubjectsSearchParam', $this->_requestParam);
        $interface->assign('deferredOLSubjectsSearchString', $this->_lookfor);
        $interface->assign(
            'deferredOLSubjectsSearchType',
            isset($_REQUEST['type']) ? $_REQUEST['type'] : ''
        );
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
        return 'Search/Recommend/OpenLibrarySubjectsDeferred.tpl';
    }
}

?>