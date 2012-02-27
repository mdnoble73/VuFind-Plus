<?php
/**
 * Search Object Authority class
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
 * @package  SearchObject
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_search_object Wiki
 */
require_once 'sys/Proxy_Request.php';   // needed for constant definitions
require_once 'sys/SearchObject/Base.php';
require_once 'RecordDrivers/Factory.php';

/**
 * Search Object Authority class
 *
 * This is the implementation of the SearchObjectBase class providing access to
 * the Solr authority core.
 *
 * @category VuFind
 * @package  SearchObject
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_search_object Wiki
 */
class SearchObject_SolrAuth extends SearchObject_Base
{
    // SOLR QUERY
    // Parsed query
    private $_query = null;
    // Facets
    private $_facetLimit = 30;
    private $_facetOffset = null;
    private $_facetPrefix = null;
    private $_facetSort = null;
    // Index
    private $_index = null;
    // Field List
    private $_fields = 'score';
    // HTTP Method
    //private $_method = HTTP_REQUEST_METHOD_GET;
    private $_method = HTTP_REQUEST_METHOD_POST;
    // Result
    private $_indexResult;

    // OTHER VARIABLES
    // Index
    private $_indexEngine = null;
    // Used to pass hidden filter queries to Solr
    private $_hiddenFilters = array();

    /**
     * Constructor. Initialise some details about the server
     *
     * @access public
     */
    public function __construct()
    {
        // Call base class constructor
        parent::__construct();

        global $configArray;

        // Initialise the index
        $this->_indexEngine = ConnectionManager::connectToIndex('SolrAuth');

        // Set up appropriate results action:
        $this->resultsModule = 'Authority';
        $this->resultsAction = 'Search';

        // Set up basic and advanced search types; default to basic.
        $this->searchType = $this->basicSearchType = 'Authority';
        $this->advancedSearchType = 'AuthorityAdvanced';

        // Get default facet settings
        $this->facetConfig = array();
        $this->recommendIni = 'authority';

        // Load search preferences:
        $searchSettings = getExtraConfigArray('authority');
        if (isset($searchSettings['General']['default_handler'])) {
            $this->defaultIndex = $searchSettings['General']['default_handler'];
        }
        if (isset($searchSettings['General']['default_sort'])) {
            $this->defaultSort = $searchSettings['General']['default_sort'];
        }
        if (isset($searchSettings['Basic_Searches'])) {
            $this->basicTypes = $searchSettings['Basic_Searches'];
        }
        if (isset($searchSettings['Advanced_Searches'])) {
            $this->advancedTypes = $searchSettings['Advanced_Searches'];
        }

        // Load sort preferences (or defaults if none in .ini file):
        if (isset($searchSettings['Sorting'])) {
            $this->sortOptions = $searchSettings['Sorting'];
        } else {
            $this->sortOptions = array(
                'relevance' => 'sort_relevance',
                'heading' => 'Heading'
            );
        }

        // Load Spelling preferences
        $this->spellcheck    = false;
    }

    /**
     * Initialise the object from the global
     *  search parameters in $_REQUEST.
     *
     * @return boolean
     * @access public
     */
    public function init()
    {
        global $module;
        global $action;

        // Call the standard initialization routine in the parent:
        parent::init();

        //********************
        // Check if we have a saved search to restore -- if restored successfully,
        // our work here is done; if there is an error, we should report failure;
        // if restoreSavedSearch returns false, we should proceed as normal.
        $restored = $this->restoreSavedSearch();
        if ($restored === true) {
            return true;
        } else if (PEAR::isError($restored)) {
            return false;
        }

        //********************
        // Initialize standard search parameters
        $this->initView();
        $this->initPage();
        $this->initSort();
        $this->initFilters();

        //********************
        // Basic Search logic
        if ($this->initBasicSearch()) {
            // If we found a basic search, we don't need to do anything further.
        } else {
            $this->initAdvancedSearch();
        }

        // If a query override has been specified, log it here
        if (isset($_REQUEST['q'])) {
            $this->_query = $_REQUEST['q'];
        }

        return true;
    } // End init()

    /**
     * Used during repeated deminification (such as search history).
     *   To scrub fields populated above.
     *
     * @return void
     * @access private
     */
    protected function purge()
    {
        // Call standard purge:
        parent::purge();

        // Make some Solr-specific adjustments:
        $this->_query        = null;
    }

    /**
     * Basic 'getter' for query string.
     *
     * @return string
     * @access public
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Basic 'getter' for index engine.
     *
     * @return object
     * @access public
     */
    public function getIndexEngine()
    {
        return $this->_indexEngine;
    }

    /**
     * Return the field (index) searched by a basic search
     *
     * @return string The searched index
     * @access public
     */
    public function getSearchIndex()
    {
        // Use normal parent method for non-advanced searches.
        if ($this->searchType == $this->basicSearchType) {
            return parent::getSearchIndex();
        } else {
            return null;
        }
    }

    /**
     * Set an overriding string.
     *
     * @param string $newQuery Query string
     *
     * @return void
     * @access public
     */
    public function setQueryString($newQuery)
    {
        $this->_query = $newQuery;
    }

    /**
     * Set an overriding facet sort order.
     *
     * @param string $newSort Sort string
     *
     * @return void
     * @access public
     */
    public function setFacetSortOrder($newSort)
    {
        // As of Solr 1.4 valid values are:
        // 'count' = relevancy ranked
        // 'index' = index order, most likely alphabetical
        // more info : http://wiki.apache.org/solr/SimpleFacetParameters#facet.sort
        if ($newSort == 'count' || $newSort == 'index') {
            $this->_facetSort = $newSort;
        }
    }

    /**
     * Add a prefix to facet requirements. Serves to
     *    limits facet sets to smaller subsets.
     *
     *  eg. all facet data starting with 'R'
     *
     * @param string $prefix Data for prefix
     *
     * @return void
     * @access public
     */
    public function addFacetPrefix($prefix)
    {
        $this->_facetPrefix = $prefix;
    }

    /**
     * Get error message from index response, if any.  This will only work if
     * processSearch was called with $returnIndexErrors set to true!
     *
     * @return mixed false if no error, error string otherwise.
     * @access public
     */
    public function getIndexError()
    {
        return isset($this->_indexResult['error']) ?
            $this->_indexResult['error'] : false;
    }

    /**
     * Add a hidden (i.e. not visible in facet controls) filter query to the object.
     *
     * @param string $fq Filter query for Solr.
     *
     * @return void
     * @access public
     */
    public function addHiddenFilter($fq)
    {
        $this->_hiddenFilters[] = $fq;
    }

    /**
     * Actually process and submit the search
     *
     * @param bool $returnIndexErrors Should we die inside the index code if we
     * encounter an error (false) or return it for access via the getIndexError()
     * method (true)?
     * @param bool $recommendations   Should we process recommendations along with
     * the search itself?
     *
     * @return object                 Solr result structure (for now)
     * @access public
     */
    public function processSearch(
        $returnIndexErrors = false, $recommendations = false
    ) {
        // Our search has already been processed in init()
        $search = $this->searchTerms;

        // Build a recommendations module appropriate to the current search:
        if ($recommendations) {
            $this->initRecommendations();
        }

        // Build Query
        $query = $this->_indexEngine->buildQuery($search);
        if (PEAR::isError($query)) {
            return $query;
        }

        // Only use the query we just built if there isn't an override in place.
        if ($this->_query == null) {
            $this->_query = $query;
        }

        // Define Filter Query
        $filterQuery = $this->_hiddenFilters;
        foreach ($this->filterList as $field => $filter) {
            foreach ($filter as $value) {
                // Special case -- allow trailing wildcards:
                if (substr($value, -1) == '*') {
                    $filterQuery[] = "$field:$value";
                } else {
                    $filterQuery[] = "$field:\"$value\"";
                }
            }
        }

        // If we are only searching one field use the DisMax handler
        //    for that field. If left at null let solr take care of it
        if (count($search) == 1 && isset($search[0]['index'])) {
            $this->_index = $search[0]['index'];
        }

        // Build a list of facets we want from the index
        $facetSet = array();
        if (!empty($this->facetConfig)) {
            $facetSet['limit'] = $this->_facetLimit;
            foreach ($this->facetConfig as $facetField => $facetName) {
                $facetSet['field'][] = $facetField;
            }
            if ($this->_facetOffset != null) {
                $facetSet['offset'] = $this->_facetOffset;
            }
            if ($this->_facetPrefix != null) {
                $facetSet['prefix'] = $this->_facetPrefix;
            }
            if ($this->_facetSort != null) {
                $facetSet['sort'] = $this->_facetSort;
            }
        }

        // Get time before the query
        $this->startQueryTimer();

        // The "relevance" sort option is a VuFind reserved word; we need to make
        // this null in order to achieve the desired effect with Solr:
        $finalSort = ($this->sort == 'relevance') ? null : $this->sort;

        // The first record to retrieve:
        //  (page - 1) * limit = start
        $recordStart = ($this->page - 1) * $this->limit;
        $this->_indexResult = $this->_indexEngine->search(
            $this->_query,     // Query string
            $this->_index,     // DisMax Handler
            $filterQuery,      // Filter query
            $recordStart,      // Starting record
            $this->limit,      // Records per page
            $facetSet,         // Fields to facet on
            '',                // Spellcheck query
            '',                // Spellcheck dictionary
            $finalSort,        // Field to sort on
            $this->_fields,    // Fields to return
            $this->_method,    // HTTP Request method
            $returnIndexErrors // Include errors in response?
        );

        // Get time after the query
        $this->stopQueryTimer();

        // How many results were there?
        $this->resultsTotal = $this->_indexResult['response']['numFound'];

        // If extra processing is needed for recommendations, do it now:
        if ($recommendations && is_array($this->recommend)) {
            foreach ($this->recommend as $currentSet) {
                foreach ($currentSet as $current) {
                    $current->process();
                }
            }
        }

        // Return the result set
        return $this->_indexResult;
    }

    /**
     * Returns the stored list of facets for the last search
     *
     * @param array $filter         Array of field => on-screen description listing
     * all of the desired facet fields; set to null to get all configured values.
     * @param bool  $expandingLinks If true, we will include expanding URLs (i.e.
     * get all matches for a facet, not just a limit to the current search) in the
     * return array.
     *
     * @return array                Facets data arrays
     * @access public
     */
    public function getFacetList($filter = null, $expandingLinks = false)
    {
        // If there is no filter, we'll use all facets as the filter:
        if (is_null($filter)) {
            $filter = $this->facetConfig;
        }

        // Start building the facet list:
        $list = array();

        // If we have no facets to process, give up now
        if (!is_array($this->_indexResult['facet_counts']['facet_fields'])) {
            return $list;
        }

        // Loop through every field returned by the result set
        $validFields = array_keys($filter);
        foreach ($this->_indexResult['facet_counts']['facet_fields'] as $field => $data) {
            // Skip filtered fields and empty arrays:
            if (!in_array($field, $validFields) || count($data) < 1) {
                continue;
            }
            // Initialize the settings for the current field
            $list[$field] = array();
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = array();
            foreach ($data as $facet) {
                // Initialize the array of data about the current facet:
                $currentSettings = array();
                $currentSettings['value'] = $facet[0];
                $currentSettings['count'] = $facet[1];
                $currentSettings['isApplied'] = false;
                $currentSettings['url']
                    = $this->renderLinkWithFilter("$field:".$facet[0]);
                // If we want to have expanding links (all values matching the facet)
                // in addition to limiting links (filter current search with facet),
                // do some extra work:
                if ($expandingLinks) {
                    $currentSettings['expandUrl']
                        = $this->getExpandingFacetLink($field, $facet[0]);
                }
                // Is this field a current filter?
                if (in_array($field, array_keys($this->filterList))) {
                    // and is this value a selected filter?
                    if (in_array($facet[0], $this->filterList[$field])) {
                        $currentSettings['isApplied'] = true;
                    }
                }

                // Store the collected values:
                $list[$field]['list'][] = $currentSettings;
            }
        }
        return $list;
    }

    /**
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @return array Spelling suggestion data arrays
     * @access public
     */
    public function getSpellingSuggestions()
    {
        /* Not implemented:
         */
        return array();
    }
}
?>