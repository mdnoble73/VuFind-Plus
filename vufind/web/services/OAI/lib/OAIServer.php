<?php
/**
 * OAI Server class
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
require_once 'services/MyResearch/lib/Change_tracker.php';
require_once 'services/MyResearch/lib/Oai_resumption.php';

/**
 * OAI Server class
 *
 * This class provides OAI server functionality.
 *
 * @category VuFind
 * @package  OAI_Server
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/tracking_record_changes Wiki
 */
class OAIServer
{
    protected $baseURL;                         // Repository base URL
    protected $params;                          // Incoming request parameters
    protected $index;                           // Connection to Solr index
    protected $core = 'biblio';                 // What Solr core are we serving up?
    protected $iso8601 = 'Y-m-d\TH:i:s\Z';      // ISO-8601 date format
    protected $pageSize = 100;                  // Records per page in lists
    protected $setField = null;                 // Solr field for set membership

    // Supported metadata formats:
    protected $metadataFormats = array();

    // Namespace used for ID prefixing (if any):
    protected $idNamespace = null;

    // Values used in "Identify" response:
    protected $repositoryName = 'VuFind';
    protected $earliestDatestamp = '2000-01-01T00:00:00Z';
    protected $adminEmail;

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
        global $configArray;

        $this->baseURL = $baseURL;
        $this->params = isset($params) && is_array($params) ? $params : array();
        $this->initializeMetadataFormats(); // Load details on supported formats
        $this->initializeSettings();        // Load config.ini settings
        $this->initializeIndex();           // Set up Solr connection
    }

    /**
     * Respond to the OAI-PMH request.
     *
     * @return void
     * @access public
     */
    public function respond()
    {
        if (!$this->hasParam('verb')) {
            $this->showError('badArgument', 'Missing Verb Argument');
        } else {
            switch($this->params['verb']) {
            case 'GetRecord':
                $this->getRecord();
                break;
            case 'Identify':
                $this->identify();
                break;
            case 'ListIdentifiers':
            case 'ListRecords':
                $this->listRecords($this->params['verb']);
                break;
            case 'ListMetadataFormats':
                $this->listMetadataFormats();
                break;
            case 'ListSets':
                $this->listSets();
                break;
            default:
                $this->showError('badVerb', 'Illegal OAI Verb');
                break;
            }
        }
    }

    /**
     * Assign necessary interface variables to display a deleted record.
     *
     * @param object $tracker A populated Change_tracker object.
     *
     * @return void
     * @access protected
     */
    protected function assignDeleted($tracker)
    {
        global $interface;

        $interface->assign('recordId', $this->prefixID($tracker->id));
        $interface->assign('recordSets', array());  // deleted sets not supported
        $interface->assign(
            'recordDate',
            date($this->iso8601, $this->normalizeDate($tracker->deleted))
        );
        $interface->assign('recordStatus', 'deleted');
        $interface->assign('recordMetadata', '');
    }

    /**
     * Assign necessary interface variables to display a non-deleted record.
     *
     * @param array  $record A record from the Solr response.
     * @param string $format Metadata format to obtain (false for none)
     *
     * @return bool          True on success, false on error.
     * @access protected
     */
    protected function assignNonDeleted($record, $format)
    {
        global $interface;

        // Get the XML (and display an error if it is unsupported):
        $recordDriver = RecordDriverFactory::initRecordDriver($record);
        if ($format === false) {
            $xml = '';      // no metadata if in header-only mode!
        } else {
            $xml = $recordDriver->getXML($format);
            if ($xml === false) {
                return false;
            }
        }

        // Pass on the relevant metadata:
        $interface->assign(
            'recordId', $this->prefixID($recordDriver->getUniqueID())
        );
        if (!is_null($this->setField) && !empty($record[$this->setField])) {
            $sets = $record[$this->setField];
        } else {
            $sets = array();
        }
        $interface->assign('recordSets', $sets);
        $interface->assign(
            'recordDate', isset($record['last_indexed'])
            ? $record['last_indexed'] : date($this->iso8601)
        );
        $interface->assign('recordStatus', '');
        $interface->assign('recordMetadata', $xml);
        return true;
    }

    /**
     * Respond to a GetRecord request.
     *
     * @return void
     * @access protected
     */
    protected function getRecord()
    {
        // Validate parameters
        if (!$this->hasParam('metadataPrefix')) {
            return $this->showError('badArgument', 'Missing Metadata Prefix');
        }
        if (!$this->hasParam('identifier')) {
            return $this->showError('badArgument', 'Missing Identifier');
        }

        // Retrieve the record from the index
        if ($record = $this->loadRecord($this->params['identifier'])) {
            if (!$this->assignNonDeleted($record, $this->params['metadataPrefix'])) {
                return $this->showError('cannotDisseminateFormat', 'Unknown Format');
            }
        } else {
            // No record in index -- is this deleted?
            $tracker = new Change_tracker();
            $tracker->core = $this->core;
            $tracker->id = $this->stripID($this->params['identifier']);
            if ($tracker->find() && $tracker->fetch() && !empty($tracker->deleted)) {
                $this->assignDeleted($tracker);
            } else {
                // Not deleted and not found in index -- error!
                return $this->showError('idDoesNotExist', 'Unknown Record');
            }
        }

        // Display the record:
        $this->showResponse('get_record.tpl');
    }

    /**
     * Was the specified parameter provided?
     *
     * @param string $param Name of the parameter to check.
     *
     * @return bool         True if parameter is set and non-empty.
     * @access protected
     */
    protected function hasParam($param)
    {
        return (isset($this->params[$param]) && !empty($this->params[$param]));
    }

    /**
     * Respond to an Identify request:
     *
     * @return void
     * @access protected
     */
    protected function identify()
    {
        global $interface;

        $interface->assign('repositoryName', $this->repositoryName);
        $interface->assign('baseURL', $this->baseURL);
        $interface->assign('protocolVersion', '2.0');
        $interface->assign('earliestDatestamp', $this->earliestDatestamp);
        $interface->assign('deletedRecord', 'transient');
        $interface->assign('granularity', 'YYYY-MM-DDThh:mm:ssZ');
        $interface->assign('adminEmail', $this->adminEmail);
        $interface->assign('idNamespace', $this->idNamespace);

        $this->showResponse('identify.tpl');
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
        $this->index = ConnectionManager::connectToIndex();
    }

    /**
     * Load data about metadata formats.  (This is called by the constructor
     * and is only a separate method to allow easy override by child classes).
     *
     * @return void
     * @access protected
     */
    protected function initializeMetadataFormats()
    {
        $this->metadataFormats['oai_dc'] = array(
            'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $this->metadataFormats['marc21'] = array(
            'schema' => 'http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd',
            'namespace' => 'http://www.loc.gov/MARC21/slim');
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

        // Override default repository name if configured:
        if (isset($configArray['OAI']['repository_name'])) {
            $this->repositoryName = $configArray['OAI']['repository_name'];
        }

        // Override default ID namespace if configured:
        if (isset($configArray['OAI']['identifier'])) {
            $this->idNamespace = $configArray['OAI']['identifier'];
        }

        // Use either OAI-specific or general email address; we must have SOMETHING.
        $this->adminEmail = isset($configArray['OAI']['admin_email']) ?
            $configArray['OAI']['admin_email'] : $configArray['Site']['email'];

        // Use a Solr field to determine sets, if configured:
        if (isset($configArray['OAI']['set_field'])) {
            $this->setField = $configArray['OAI']['set_field'];
        }
    }

    /**
     * Respond to a ListMetadataFormats request.
     *
     * @return void
     * @access protected
     */
    protected function listMetadataFormats()
    {
        global $interface;

        // If a specific ID was provided, try to load the related record; otherwise,
        // set $recordDriver to false so we know it is a generic request.
        if (isset($this->params['identifier'])) {
            if (!($record = $this->loadRecord($this->params['identifier']))) {
                return $this->showError('idDoesNotExist', 'Unknown Record');
            }
            $recordDriver = RecordDriverFactory::initRecordDriver($record);
        } else {
            $recordDriver = false;
        }

        // Loop through all available metadata formats and see if they apply in
        // the current context (all apply if $recordDriver is false, since that
        // means that no specific record ID was requested; otherwise, they only
        // apply if the current record driver supports them):
        $supported = array();
        foreach ($this->metadataFormats as $prefix => $details) {
            if ($recordDriver === false
                || $recordDriver->getXML($prefix) !== false
            ) {
                $supported[$prefix] = $details;
            }
        }

        // Display the response:
        $interface->assign('supported', $supported);
        $this->showResponse('list-metadata.tpl');
    }

    /**
     * Respond to a ListIdentifiers or ListRecords request (the $verb parameter
     * determines the exact format of the response).
     *
     * @param string $verb 'ListIdentifiers' or 'ListRecords'
     *
     * @return void
     * @access protected
     */
    protected function listRecords($verb = 'ListRecords')
    {
        global $interface;

        // Load and validate parameters; if false is returned, an error has already
        // been output.
        $params = $this->listRecordsGetParams();
        if ($params === false) {
            return;
        }

        // Normalize the provided dates into Unix timestamps.  Depending on whether
        // they come from the OAI-PMH request or the database, the format may be
        // slightly different; this ensures they are reduced to a consistent value!
        $from = $this->normalizeDate($params['from']);
        $until = $this->normalizeDate($params['until']);
        if (!$this->listRecordsValidateDates($from, $until)) {
            return;
        }

        // Initialize the array of XML chunks to include in our response:
        $xmlParts = array();

        // Copy the cursor from the parameters so we can track our current position
        // separately from our initial position!
        $currentCursor = $params['cursor'];

        // The template for displaying a single record varies based on the verb:
        $recordTemplate = $verb == 'ListRecords' ?
            'OAI/record.tpl' : 'OAI/header.tpl';

        // Get deleted records in the requested range (if applicable):
        $tracker = $this->listRecordsGetDeleted($from, $until);
        $deletedCount = $tracker->find(false);
        if ($currentCursor < $deletedCount) {
            $tracker->limit($currentCursor, $this->pageSize);
            $tracker->find(false);
            while ($tracker->fetch()) {
                $this->assignDeleted($tracker);
                $xmlParts[] = $interface->fetch($recordTemplate);
                $currentCursor++;
            }
        }

        // Figure out how many Solr records we need to display (and where to start):
        if ($currentCursor >= $deletedCount) {
            $solrOffset = $currentCursor - $deletedCount;
        } else {
            $solrOffset = 0;
        }
        $solrLimit = ($params['cursor'] + $this->pageSize) - $currentCursor;

        // Get non-deleted records from the Solr index:
        $result = $this->listRecordsGetNonDeleted(
            $from, $until, $solrOffset, $solrLimit, $params['set']
        );
        $nonDeletedCount = $result['response']['numFound'];
        $format = $verb == 'ListIdentifiers' ? false : $params['metadataPrefix'];
        foreach ($result['response']['docs'] as $doc) {
            if (!$this->assignNonDeleted($doc, $format)) {
                $this->unexpectedError();
            }
            $xmlParts[] = $interface->fetch($recordTemplate);
            $currentCursor++;
        }

        // If our cursor didn't reach the last record, we need a resumption token!
        $listSize = $deletedCount + $nonDeletedCount;
        if ($listSize > $currentCursor) {
            $xmlParts[] = $this->saveResumptionToken(
                $params, $currentCursor, $listSize
            );
        } else if ($solrOffset > 0) {
            // If we reached the end of the list but there is more than one page, we
            // still need to display an empty <resumptionToken> tag:
            $interface->assign('oldCursor', $params['cursor']);
            $interface->assign('resumptionToken', '');
            $interface->assign('listSize', $listSize);
            $xmlParts[] = $interface->fetch('OAI/resumption.tpl');
        }

        $interface->assign('verb', $verb);
        $interface->assign('xmlParts', $xmlParts);
        $this->showResponse('list.tpl');
    }

    /**
     * Respond to a ListSets request.
     *
     * @return void
     * @access protected
     */
    protected function listSets()
    {
        global $interface;

        // Resumption tokens are not currently supported for this verb:
        if ($this->hasParam('resumptionToken')) {
            return $this->showError(
                'badResumptionToken', 'Invalid resumption token'
            );
        }

        // If no set field is enabled, we can't provide a set list:
        if (is_null($this->setField)) {
            return $this->showError('noSetHierarchy', 'Sets not supported');
        }

        // If we got this far, we can load all available set values.  For now,
        // we'll assume that this list is short enough to load in a single response;
        // it may be necessary to implement a resumption token mechanism if this
        // proves not to be the case:
        $facets = array('field' => $this->setField, 'limit' => -1);
        $result = $this->index->search('*:*', null, null, 0, 0, $facets);

        // Throw a fatal error if Solr fails:
        if (empty($result) || !isset($result['response'])
            || !isset($result['response']['docs'])
        ) {
            $this->unexpectedError();
        }

        // Extract facet values from the Solr response:
        $values = array();
        foreach ($result['facet_counts']['facet_fields'][$this->setField] as $x) {
            $values[] = array('spec' => $x[0], 'name' => $x[0]);
        }

        // Display the list:
        $interface->assign('sets', $values);
        $this->showResponse('list-sets.tpl');
    }

    /**
     * Get an object to list deleted records in the specified range.
     *
     * @param int $from  Start date.
     * @param int $until End date.
     *
     * @return object    Change_tracker object.
     * @access protected
     */
    protected function listRecordsGetDeleted($from, $until)
    {
        $tracker = new Change_tracker();
        $tracker->core = $this->core;
        $tracker->whereAdd("\"deleted\" >= '" . date('Y-m-d H:i:s', $from) . "'");
        $tracker->whereAdd("\"deleted\" <= '" . date('Y-m-d H:i:s', $until) . "'");
        $tracker->orderBy('deleted');
        return $tracker;
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
        // Construct a range query based on last indexed time:
        $query = 'last_indexed:[' . date($this->iso8601, $from) . ' TO ' .
            date($this->iso8601, $until) . ']';

        // Apply filters as needed.
        $filter = array();
        if (!empty($set) && !is_null($this->setField)) {
            $filter[] = $this->setField . ':"' . $set . '"';
        }

        // Perform a Solr search:
        $result = $this->index->search(
            $query, null, $filter, $offset, $limit, null, '', null, 'last_indexed'
        );

        // Throw a fatal error if Solr fails:
        if (empty($result) || !isset($result['response'])
            || !isset($result['response']['docs'])
        ) {
            $this->unexpectedError();
        }

        // Return our results:
        return $result;
    }

    /**
     * Get parameters for use in the listRecords method.
     *
     * @return mixed Array of parameters or false on error
     * @access protected
     */
    protected function listRecordsGetParams()
    {
        // If we received a resumption token, use it to override any existing
        // parameters or fail if it is invalid.
        if (!empty($this->params['resumptionToken'])) {
            $params = $this->loadResumptionToken($this->params['resumptionToken']);
            if ($params === false) {
                return $this->showError(
                    'badResumptionToken', 'Invalid or expired resumption token'
                );
            }

            // Merge restored parameters with incoming parameters:
            $params = array_merge($params, $this->params);
        } else {
            // No resumption token?  Use the provided parameters:
            $params = $this->params;

            // Make sure we don't act on any user-provided cursor settings; this
            // value should only be set in association with resumption tokens!
            $params['cursor'] = 0;

            // Set default date range if not already provided:
            if (empty($params['from'])) {
                $params['from'] = $this->earliestDatestamp;
            }
            if (empty($params['until'])) {
                $params['until'] = date($this->iso8601);
            }
        }

        // If no set field is configured and a set parameter comes in, we have a
        // problem:
        if (is_null($this->setField) && isset($params['set'])
            && !empty($params['set'])
        ) {
            return $this->showError('noSetHierarchy', 'Sets not supported');
        }

        // Validate requested metadata format:
        $prefixes = array_keys($this->metadataFormats);
        if (!in_array($params['metadataPrefix'], $prefixes)) {
            return $this->showError('cannotDisseminateFormat', 'Unknown Format');
        }

        return $params;
    }

    /**
     * Validate the from and until parameters for the listRecords method.
     *
     * @param int $from  Timestamp for start date.
     * @param int $until Timestamp for end date.
     *
     * @return bool      True if valid, false if not.
     * @access protected
     */
    protected function listRecordsValidateDates($from, $until)
    {
        // Validate dates:
        if (!$from || !$until) {
            return $this->showError('badArgument', 'Bad Date Format');
        }
        if ($from > $until) {
            return $this->showError(
                'badArgument', 'End date must be after start date'
            );
        }
        if ($from < $this->normalizeDate($this->earliestDatestamp)) {
            return $this->showError(
                'badArgument', 'Start date must be after earliest date'
            );
        }

        // If we got this far, everything is valid!
        return true;
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
        // Strip the ID prefix, if necessary:
        $id = $this->stripID($id);
        if ($id !== false) {
            return $this->index->getRecord($id);
        }
        return false;
    }

    /**
     * Load parameters associated with a resumption token.
     *
     * @param string $token The resumption token to look up
     *
     * @return array        Parameters associated with token
     * @access protected
     */
    protected function loadResumptionToken($token)
    {
        // Create object for loading tokens:
        $search = new Oai_resumption();

        // Clean up expired records before doing our search:
        $search->removeExpired();

        // Load the requested token if it still exists:
        $search->id = $token;
        if ($search->find(true)) {
            return $search->restoreParams();
        }

        // If we got this far, the token is invalid or expired:
        return false;
    }

    /**
     * Normalize a date to a Unix timestamp.
     *
     * @param string $date Date (ISO-8601 or YYYY-MM-DD HH:MM:SS)
     *
     * @return integer     Unix timestamp (or false if $date invalid)
     * @access protected
     */
    protected function normalizeDate($date)
    {
        // Remove timezone markers -- we don't want PHP to outsmart us by adjusting
        // the time zone!
        $date = str_replace(array('T', 'Z'), array(' ', ''), $date);

        // Translate to a timestamp:
        return strtotime($date);
    }

    /**
     * Prepend the OAI prefix to the provided ID number.
     *
     * @param string $id The ID to update.
     *
     * @return string    The prefixed ID.
     * @access protected
     */
    protected function prefixID($id)
    {
        $prefix = empty($this->idNamespace)
            ? '' : 'oai:' . $this->idNamespace . ':';
        return $prefix . $id;
    }

    /**
     * Generate a resumption token to continue the current operation.
     *
     * @param array $params        Current operational parameters.
     * @param int   $currentCursor Current cursor position in search results.
     * @param int   $listSize      Total size of search results.
     *
     * @return string              XML details about token.
     * @access protected
     */
    protected function saveResumptionToken($params, $currentCursor, $listSize)
    {
        global $interface;

        // Save the old cursor position to the template before overwriting it for
        // storage in the database!
        $interface->assign('oldCursor', $params['cursor']);
        $params['cursor'] = $currentCursor;

        // Save everything to the database:
        $search = new Oai_resumption();
        $search->saveParams($params);
        $expire = time() + 24 * 60 * 60;
        $search->expires = date('Y-m-d H:i:s', $expire);
        $token = $search->insert();

        // Send remaining details to the template:
        $interface->assign('resumptionToken', $token);
        $interface->assign('tokenExpiration', date($this->iso8601, $expire));
        $interface->assign('listSize', $listSize);

        // Build the XML:
        return $interface->fetch('OAI/resumption.tpl');
    }

    /**
     * Display an error response.
     *
     * @param string $code    The error code to display
     * @param string $message The error string to display
     *
     * @return bool           Always returns false
     * @access protected
     */
    protected function showError($code, $message)
    {
        global $interface;

        $interface->assign('errorCode', $code);
        $interface->assign('errorMessage', $message);

        // Certain errors should not echo parameters:
        $echoParams = !($code == 'badVerb' || $code == 'badArgument');
        $this->showResponse('error.tpl', $echoParams);

        return false;
    }

    /**
     * Display an OAI-PMH response (shared support method used by various
     * response-specific methods).
     *
     * @param string $template   The template file for the response contents
     * @param bool   $echoParams Include params in <request> tag?
     *
     * @return void
     * @access protected
     */
    protected function showResponse($template, $echoParams = true)
    {
        global $interface;

        header('Content-type: text/xml');
        $interface->assign('requestURL', $this->baseURL);
        $interface->assign('responseDate', date($this->iso8601));
        $interface->assign('requestParams', $echoParams ? $this->params : array());
        $interface->setTemplate($template);
        $interface->display('OAI/layout.tpl');
    }

    /**
     * Strip the OAI prefix from the provided ID number.
     *
     * @param string $id The ID to strip.
     *
     * @return string    The stripped ID (false if prefix invalid).
     * @access protected
     */
    protected function stripID($id)
    {
        // No prefix?  No stripping!
        if (empty($this->idNamespace)) {
            return $id;
        }

        // Prefix?  Strip it off and return the stripped version if valid:
        $prefix = 'oai:' . $this->idNamespace . ':';
        $prefixLen = strlen($prefix);
        if (!empty($prefix) && substr($id, 0, $prefixLen) == $prefix) {
            return substr($id, $prefixLen);
        }

        // Invalid prefix -- unrecognized ID:
        return false;
    }

    /**
     * Die with an unexpected error code (when something outside the scope of
     * OAI-PMH fails).
     *
     * @return void
     * @access protected
     */
    protected function unexpectedError()
    {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Unexpected fatal error.";

        // Dump out 1k of text to ensure that IE displays the message correctly:
        for ($x = 0; $x < 1024; $x++) {
            echo ' ';
        }
        die();
    }
}
?>