<?php
/**
 * Voyager ILS Driver
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
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */

require_once 'Voyager.php';

/**
 * Voyager Restful ILS Driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
class VoyagerRestful extends Voyager
{
    /**
     * Constructor
     *
     * @param string $configFile Name of configuration file to load (relative to
     * web/conf folder; defaults to VoyagerRestful.ini).
     *
     * @access public
     */
    public function __construct($configFile = 'VoyagerRestful.ini')
    {
        // Call the parent's constructor...
        parent::__construct($configFile);

        // Define Voyager Restful Settings
        $this->ws_host = $this->config['WebServices']['host'];
        $this->ws_port = $this->config['WebServices']['port'];
        $this->ws_app = $this->config['WebServices']['app'];
        $this->ws_dbKey = $this->config['WebServices']['dbKey'];
        $this->ws_patronHomeUbId = $this->config['WebServices']['patronHomeUbId'];
        $this->ws_pickUpLocations
            = (isset($this->config['pickUpLocations']))
            ? $this->config['pickUpLocations'] : false;
        $this->defaultPickUpLocation
            = $this->config['Holds']['defaultPickUpLocation'];
    }

    /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     * @access public
     */
    public function getConfig($function)
    {
        if (isset($this->config[$function]) ) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
        return $functionConfig;
    }

    /**
     * Private support method for VuFind Hold Logic. Take an array of status strings
     * and determines whether or not an item is holdable based on the
     * valid_hold_statuses settings in configuration file
     *
     * @param array $statusArray The status codes to analyze.
     *
     * @return bool Whether an item is holdable
     * @access private
     */
    private function _isHoldable($statusArray)
    {
        // User defined hold behaviour
        $is_holdable = true;

        if (isset($this->config['Holds']['valid_hold_statuses'])) {
            $valid_hold_statuses_array
                = explode(":", $this->config['Holds']['valid_hold_statuses']);

            if (count($valid_hold_statuses_array > 0)) {
                foreach ($statusArray as $status) {
                    if (!in_array($status, $valid_hold_statuses_array)) {
                        $is_holdable = false;
                    }
                }
            }
        }
        return $is_holdable;
    }

    /**
     * Private support method for VuFind Hold Logic. Takes an item type id
     * and determines whether or not an item is borrowable based on the
     * non_borrowable settings in configuration file
     *
     * @param string $itemTypeID The item type id to analyze.
     *
     * @return bool Whether an item is borrowable
     * @access private
     */
    private function _isBorrowable($itemTypeID)
    {
        $is_borrowable = true;
        if (isset($this->config['Holds']['non_borrowable'])) {
            $non_borrow = explode(":", $this->config['Holds']['non_borrowable']);
            if (in_array($itemTypeID, $non_borrow)) {
                $is_borrowable = false;
            }
        }

        return $is_borrowable;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array $id A Bibliographic id
     *
     * @return array Keyed data for use in an sql query
     * @access protected
     */
    protected function getHoldingItemsSQL($id)
    {
        $sqlArray = parent::getHoldingItemsSQL($id);
        $sqlArray['expressions'][] = "ITEM.ITEM_TYPE_ID";

        return $sqlArray;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array $sqlRow SQL Row Data
     *
     * @return array Keyed data
     * @access protected
     */
    protected function processHoldingRow($sqlRow)
    {
        $row = parent::processHoldingRow($sqlRow);
        $row += array('item_id' => $sqlRow['ITEM_ID'], '_fullRow' => $sqlRow);
        return $row;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array $data   Item Data
     * @param mixed $patron Patron Data or boolean false
     *
     * @return array Keyed data
     * @access protected
     */

    protected function processHoldingData($data, $patron = false)
    {
        $holding = parent::processHoldingData($data, $patron);

        foreach ($holding as $i => $row) {
            $is_borrowable = $this->_isBorrowable($row['_fullRow']['ITEM_TYPE_ID']);
            $is_holdable = $this->_isHoldable($row['_fullRow']['STATUS_ARRAY']);
            // If the item cannot be borrowed or if the item is not holdable,
            // set is_holdable to false
            if (!$is_borrowable || !$is_holdable) {
                $is_holdable = false;
            }

            // Only used for driver generated hold links
            $addLink = false;

            // Hold Type - If we have patron data, we can use it to dermine if a
            // hold link should be shown
            if ($patron) {
                $holdType = $this->_determineHoldType(
                    $row['id'], $row['item_id'], $patron['id']
                );
                $addLink = $holdType ? $holdType : false;
            } else {
                $holdType = "auto";
            }

            $holding[$i] += array(
                'is_holdable' => $is_holdable,
                'holdtype' => $holdType,
                'addLink' => $addLink
            );
            unset($holding[$i]['_fullRow']);
        }
        return $holding;
    }

    /**
     * Determine Renewability
     *
     * This is responsible for determining if an item is renewable
     *
     * @param string $patronId The user's patron ID
     * @param string $itemId   The Item Id of item
     *
     * @return mixed Array of the renewability status and associated
     * message
     * @access private
     */

    private function _isRenewable($patronId, $itemId)
    {

        // Build Hierarchy
        $hierarchy = array(
            "patron" => $patronId,
            "circulationActions" => "loans"
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        // Create Rest API Renewal Key
        $restItemID = $this->ws_dbKey. "|" . $itemId;

        // Add to Hierarchy
        $hierarchy[$restItemID] = false;

        $renewability = $this->_makeRequest($hierarchy, $params, "GET");
        $renewability = $renewability->children();
        $node = "reply-text";
        $reply = (string)$renewability->$node;
        if ($reply == "ok") {
            $loanAttributes = $renewability->resource->loan->attributes();
            $canRenew = (string)$loanAttributes['canRenew'];
            if ($canRenew == "Y") {
                $renewData['message'] = false;
                $renewData['renewable'] = true;
            } else {
                $renewData['message'] = "renew_item_no";
                $renewData['renewable'] = false;
            }
        } else {
            $renewData['message'] = "renew_determine_fail";
            $renewData['renewable'] = false;
        }
        return $renewData;
    }

    /**
     * Protected support method for getMyTransactions.
     *
     * @param array $sqlRow An array of keyed data
     * @param array $patron An array of keyed patron data
     *
     * @return array Keyed data for display by template files
     * @access protected
     */
    protected function processMyTransactionsData($sqlRow, $patron)
    {
        $transactions = parent::processMyTransactionsData($sqlRow, $patron);

        $renewData = $this->_isRenewable($patron['id'], $transactions['item_id']);
        $transactions['renewable'] = $renewData['renewable'];
        $transactions['message'] = $renewData['message'];

        return $transactions;
    }

     /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron Patron information returned by the patronLogin method.
     *
     * @return array        An keyed array where libray id => Library Display Name
     * @access public
     */
    public function getPickUpLocations($patron = false)
    {
        if ($this->ws_pickUpLocations) {
            foreach ($this->ws_pickUpLocations as $code => $library) {
                $pickResponse[] = array(
                    'locationID' => $code,
                    'locationDisplay' => $library
                );
            }
        } else {
            $sql = "SELECT CIRC_POLICY_LOCS.LOCATION_ID as location_id, " .
                "LOCATION.LOCATION_DISPLAY_NAME as location_name from " .
                $this->dbName . ".CIRC_POLICY_LOCS, $this->dbName.LOCATION " .
                "where CIRC_POLICY_LOCS.PICKUP_LOCATION = 'Y' ".
                "and CIRC_POLICY_LOCS.LOCATION_ID = LOCATION.LOCATION_ID";

            try {
                $sqlStmt = $this->db->prepare($sql);
                $sqlStmt->execute();
            } catch (PDOException $e) {
                return new PEAR_Error($e->getMessage());
            }

            // Read results
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $pickResponse[] = array(
                    "locationID" => $row['LOCATION_ID'],
                    "locationDisplay" => $row['LOCATION_NAME']
                );
            }
        }
        return $pickResponse;
    }

    /**
     * Get Default Pick Up Location
     *
     * Returns the default pick up location set in VoyagerRestful.ini
     *
     * @param array $patron Patron information returned by the patronLogin method.
     *
     * @return array        An keyed array where libray id => Library Display Name
     * @access public
     */
    public function getDefaultPickUpLocation($patron = false)
    {
        return $this->defaultPickUpLocation;
    }

     /**
     * Make Request
     *
     * Makes a request to the Voyager Restful API
     *
     * @param array  $hierarchy Array of key-value pairs to embed in the URL path of
     * the request (set value to false to inject a non-paired value).
     * @param array  $params    A keyed array of query data
     * @param string $mode      The http request method to use (Default of GET)
     * @param string $xml       An optional XML string to send to the API
     *
     * @return obj  A Simple XML Object loaded with the xml data returned by the API
     * @access private
     */
    private function _makeRequest($hierarchy, $params = false, $mode = "GET",
        $xml = false
    ) {
        // Build Url Base
        $urlParams = "http://{$this->ws_host}:{$this->ws_port}/{$this->ws_app}";

        // Add Hierarchy
        foreach ($hierarchy as $key => $value) {
            $hierarchyString[] = ($value !== false) ? $key. "/" . $value : $key;
        }

        // Add Params
        foreach ($params as $key => $param) {
            $queryString[] = $key. "=" . urlencode($param);
        }

        // Build Hierarchy
        $urlParams .= "/" . implode("/", $hierarchyString);

        // Build Params
        $urlParams .= "?" . implode("&", $queryString);

        // Create Proxy Request
        $client = new Proxy_Request($urlParams);

        // Select Method
        if ($mode == "POST") {
            $client->setMethod(HTTP_REQUEST_METHOD_POST);
        } else if ($mode == "PUT") {
            $client->setMethod(HTTP_REQUEST_METHOD_PUT);
            $client->addRawPostData($xml);
        } else if ($mode == "DELETE") {
            $client->setMethod(HTTP_REQUEST_METHOD_DELETE);
        } else {
            $client->setMethod(HTTP_REQUEST_METHOD_GET);
        }

        // Send Request and Retrieve Response
        $client->sendRequest();
        $xmlResponse = $client->getResponseBody();
        $oldLibXML = libxml_use_internal_errors();
        libxml_use_internal_errors(true);
        $simpleXML = simplexml_load_string($xmlResponse);
        libxml_use_internal_errors($oldLibXML);

        if ($simpleXML === false) {
            return false;
        }
        return $simpleXML;
    }

    /**
     * Build Basic XML
     *
     * Builds a simple xml string to send to the API
     *
     * @param array $xml A keyed array of xml node names and data
     *
     * @return string    An XML string
     * @access private
     */

    private function _buildBasicXML($xml)
    {
        $xmlString = "";

        foreach ($xml as $root => $nodes) {
            $xmlString .= "<" . $root . ">";

            foreach ($nodes as $nodeName => $nodeValue) {
                $xmlString .= "<" . $nodeName . ">";
                $xmlString .= htmlentities($nodeValue, ENT_COMPAT, "UTF-8");
                $xmlString .= "</" . $nodeName . ">";
            }

            $xmlString .= "</" . $root . ">";
        }

        $xmlComplete = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xmlString;

        return $xmlComplete;
    }

    /**
     * Check Account Blocks
     *
     * Checks if a user has any blocks against their account which may prevent them
     * performing certain operations
     *
     * @param string $patronId A Patron ID
     *
     * @return mixed           A boolean false if no blocks are in place and an array
     * of block reasons if blocks are in place
     * @access private
     */

    private function _checkAccountBlocks($patronId)
    {
        $blockReason = false;

        // Build Hierarchy
        $hierarchy = array(
            "patron" =>  $patronId,
            "patronStatus" => "blocks"
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        $blocks = $this->_makeRequest($hierarchy, $params);

        if ($blocks) {
            $node = "reply-text";
            $reply = (string)$blocks->$node;

            // Valid Response
            if ($reply == "ok" && isset($blocks->blocks)) {
                $blockReason = array();
                foreach ($blocks->blocks->institution->borrowingBlock
                    as $borrowBlock
                ) {
                    $blockReason[] = (string)$borrowBlock->blockReason;
                }
            }
        }

        return $blockReason;
    }

    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS
     *
     * @return array              An array of renewal information keyed by item ID
     * @access public
     */
    public function renewMyItems($renewDetails)
    {
        $renewProcessed = array();
        $renewResult = array();
        $failIDs = array();
        $patronId = $renewDetails['patron']['id'];

        // Get Account Blocks
        $finalResult['blocks'] = $this->_checkAccountBlocks($patronId);

        if ($finalResult['blocks'] === false) {
            // Add Items and Attempt Renewal
            foreach ($renewDetails['details'] as $renewID) {
                // Build an array of item ids which may be of use in the template
                // file
                $failIDs[$renewID] = "";

                // Build Hierarchy
                $hierarchy = array(
                    "patron" => $patronId,
                    "circulationActions" => "loans"
                );

                // Add Required Params
                $params = array(
                    "patron_homedb" => $this->ws_patronHomeUbId,
                    "view" => "full"
                );

                // Create Rest API Renewal Key
                $restRenewID = $this->ws_dbKey. "|" . $renewID;

                // Add to Hierarchy
                $hierarchy[$restRenewID] = false;

                // Attempt Renewal
                $renewalObj = $this->_makeRequest($hierarchy, $params, "POST");

                $process = $this->_processRenewals($renewalObj);
                if (PEAR::isError($process)) {
                    return $process;
                }
                // Process Renewal
                $renewProcessed[] = $process;
            }

            // Place Successfully processed renewals in the details array
            foreach ($renewProcessed as $renewal) {
                if ($renewal !== false) {
                    $finalResult['details'][$renewal['item_id']] = $renewal;
                    unset($failIDs[$renewal['item_id']]);
                }
            }
            // Deal with unsuccessful results
            foreach ($failIDs as $id => $junk) {
                $finalResult['details'][$id] = array(
                    "success" => false,
                    "new_date" => false,
                    "item_id" => $id,
                    "sysMessage" => ""
                );
            }
        }
        return $finalResult;
    }

    /**
     * Process Renewals
     *
     * A support method of renewMyItems which determines if the renewal attempt
     * was successful
     *
     * @param object $renewalObj A simpleXML object loaded with renewal data
     *
     * @return array             An array with the item id, success, new date (if
     * available) and system message (if available)
     * @access private
     */
    private function _processRenewals($renewalObj)
    {
        // Not Sure Why, but necessary!
        $renewal = $renewalObj->children();
        $node = "reply-text";
        $reply = (string)$renewal->$node;

        // Valid Response
        if ($reply == "ok") {
            $loan = $renewal->renewal->institution->loan;
            $itemId = (string)$loan->itemId;
            $renewalStatus = (string)$loan->renewalStatus;

            $response['item_id'] = $itemId;
            $response['sysMessage'] = $renewalStatus;

            if ($renewalStatus == "Success") {
                $dueDate = (string)$loan->dueDate;
                if (!empty($dueDate)) {
                    // Convert Voyager Format to display format
                    $newDate = $this->dateFormat->convertToDisplayDate(
                        "Y-m-d H:i", $dueDate
                    );
                    $newTime = $this->dateFormat->convertToDisplayTime(
                        "Y-m-d H:i", $dueDate
                    );
                    if (!PEAR::isError($newDate)) {
                        $response['new_date'] = $newDate;
                    }
                    if (!PEAR::isError($newTime)) {
                        $response['new_time'] = $newTime;
                    }
                }
                $response['success'] = true;
            } else {
                $response['success'] = false;
                $response['new_date'] = false;
                $response['new_time'] = false;
            }

            return $response;
        } else {
            // System Error
            return false;
        }
    }

    /**
     * Check Item Requests
     *
     * Determines if a user can place a hold or recall on a specific item
     *
     * @param string $bibId    An item's Bib ID
     * @param string $patronId The user's Patron ID
     * @param string $request  The request type (hold or recall)
     * @param string $itemId   An item's Item ID (optional)
     *
     * @return boolean         true if the request can be made, false if it cannot
     * @access private
     */
    private function _checkItemRequests($bibId, $patronId, $request, $itemId = false)
    {
        if (!empty($bibId) && !empty($patronId) && !empty($request) ) {

            $hierarchy = array();

            // Build Hierarchy
            $hierarchy['record'] = $bibId;

            if ($itemId) {
                $hierarchy['items'] = $itemId;
            }

            $hierarchy[$request] = false;

            // Add Required Params
            $params = array(
                "patron" => $patronId,
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            $check = $this->_makeRequest($hierarchy, $params, "GET", false);

            if ($check) {
                // Process
                $check = $check->children();
                $node = "reply-text";
                $reply = (string)$check->$node;

                // Valid Response
                if ($reply == "ok") {
                    if ($check->$request ) {
                        $requestAttributes = $check->$request->attributes();
                        if ($requestAttributes['allowed'] == "Y") {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Make Item Requests
     *
     * Places a Hold or Recall for a particular item
     *
     * @param string $bibId       An item's Bib ID
     * @param string $patronId    The user's Patron ID
     * @param string $request     The request type (hold or recall)
     * @param array  $requestData An array of data to submit with the request,
     * may include comment, lastInterestDate and pickUpLocation
     * @param string $itemId      An item's Item ID (optional)
     *
     * @return array             An array of data from the attempted request
     * including success, status and a System Message (if available)
     * @access private
     */
    private function _makeItemRequests($bibId, $patronId, $request,
        $requestData, $itemId = false
    ) {
        $response = array('success' => false, 'status' =>"hold_error_fail");

        if (!empty($bibId) && !empty($patronId) && !empty($requestData)
            && !empty($request)
        ) {
            $hierarchy = array();

            // Build Hierarchy
            $hierarchy['record'] = $bibId;

            if ($itemId) {
                $hierarchy['items'] = $itemId;
            }

            $hierarchy[$request] = false;

            // Add Required Params
            $params = array(
                "patron" => $patronId,
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            $xmlParameter = ("recall" == $request)
                ? "recall-parameters" : "hold-request-parameters";


            $xml[$xmlParameter] = array(
                "pickup-location" => $requestData['pickupLocation'],
                "last-interest-date" => $requestData['lastInterestDate'],
                "comment" => $requestData['comment'],
                "dbkey" => $this->ws_dbKey
            );

            // Generate XML
            $requestXML = $this->_buildBasicXML($xml);

            // Get Data
            $result = $this->_makeRequest($hierarchy, $params, "PUT", $requestXML);

            if ($result) {
                // Process
                $result = $result->children();
                $node = "reply-text";
                $reply = (string)$result->$node;

                $responseNode = "create-".$request;
                $note = (isset($result->$responseNode))
                    ? trim((string)$result->$responseNode->note) : false;

                // Valid Response
                if ($reply == "ok" && $note == "Your request was successful.") {
                    $response['success'] = true;
                    $response['status'] = "hold_success";
                } else {
                    // Failed
                    $response['sysMessage'] = $note;
                }
            }
        }
        return $response;
    }

    /**
     * Determine Hold Type
     *
     * Determines if a user can place a hold or recall on a particular item
     *
     * @param string $bibId    An item's Bib ID
     * @param string $itemId   An item's Item ID (optional)
     * @param string $patronId The user's Patron ID
     *
     * @return string          The name of the request method to use or false on
     * failure
     * @access private
     */
    private function _determineHoldType($bibId, $itemId, $patronId)
    {
        // Check for account Blocks
        if ($this->_checkAccountBlocks($patronId)) {
            return "block";
        }
        
        // Check Recalls First
        $recall = $this->_checkItemRequests($bibId, $patronId, "recall", $itemId);

        if ($recall) {
            return "recall";
        } else {
            // Check Holds
            $hold = $this->_checkItemRequests($bibId, $patronId, "hold", $itemId);
            if ($hold) {
                return "hold";
            }
        }
        return false;
    }

    /**
     * Hold Error
     *
     * Returns a Hold Error Message
     *
     * @param string $msg An error message string
     *
     * @return array An array with a success (boolean) and sysMessage key
     * @access private
     */
    private function _holdError($msg)
    {
        return array(
                    "success" => false,
                    "sysMessage" => $msg
        );
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or a PEAR error on failure of support classes
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function placeHold($holdDetails)
    {
        $patron = $holdDetails['patron'];
        $type = $holdDetails['holdtype'];
        $pickUpLocation = !empty($holdDetails['pickUpLocation'])
            ? $holdDetails['pickUpLocation'] : $this->defaultPickUpLocation;
        $itemId = $holdDetails['item_id'];
        $comment = $holdDetails['comment'];
        $bibId = $holdDetails['id'];

        // Request was initiated before patron was logged in -
        //Let's determine Hold Type now
        if ($type == "auto") {
            $type = $this->_determineHoldType($bibId, $itemId, $patron['id']);
            if (!$type || $type == "block") {
                return $this->_holdError("hold_error_blocked");
            }
        }

        // Convert last interest date from Display Format to Voyager required format
        $lastInterestDate = $this->dateFormat->convertFromDisplayDate(
            "Y-m-d", $holdDetails['requiredBy']
        );
        if (PEAR::isError($lastInterestDate)) {
            // Hold Date is invalid
            return $this->_holdError("hold_date_invalid");
        }

        $checkTime =  $this->dateFormat->convertFromDisplayDate(
            "U", $holdDetails['requiredBy']
        );
        if (PEAR::isError($checkTime) || !is_numeric($checkTime)) {
            return $checkTime;
        }

        if (time() > $checkTime) {
            // Hold Date is in the past
            return $this->_holdError("hold_date_passed");
        }

        // Make Sure Pick Up Library is Valid
        $pickUpValid = false;
        $pickUpLibs = $this->getPickUpLocations();
        foreach ($pickUpLibs as $location) {
            if ($location['locationID'] == $pickUpLocation) {
                $pickUpValid = true;
            }
        }
        if (!$pickUpValid) {
            // Invalid Pick Up Point
            return $this->_holdError("hold_invalid_pickup");
        }

        // Build Request Data
        $requestData = array(
            'pickupLocation' => $pickUpLocation,
            'lastInterestDate' => $lastInterestDate,
            'comment' => $comment
        );

        if ($this->_checkItemRequests($bibId, $patron['id'], $type, $itemId)) {
            // Attempt Request
            $result = $this->_makeItemRequests(
                $bibId, $patron['id'], $type, $requestData, $itemId
            );
            if ($result) {
                return $result;
            }
        }
        return $this->_holdError("hold_error_blocked");
    }

    /**
     * Cancel Holds
     *
     * Attempts to Cancel a hold or recall on a particular item. The
     * data in $cancelDetails['details'] is determined by getCancelHoldDetails().
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     * @access public
     */
    public function cancelHolds($cancelDetails)
    {
        $details = $cancelDetails['details'];
        $patron = $cancelDetails['patron'];
        $count = 0;
        $response = array();
        
        foreach ($details as $cancelDetails) {
            list($itemId, $cancelCode) = explode("|", $cancelDetails);

             // Create Rest API Cancel Key
            $cancelID = $this->ws_dbKey. "|" . $cancelCode;

            // Build Hierarchy
            $hierarchy = array(
                "patron" => $patron['id'],
                 "circulationActions" => "requests",
                 "holds" => $cancelID
            );

            // Add Required Params
            $params = array(
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            // Get Data
            $cancel = $this->_makeRequest($hierarchy, $params, "DELETE");

            if ($cancel) {

                // Process Cancel
                $cancel = $cancel->children();
                $node = "reply-text";
                $reply = (string)$cancel->$node;
                $count = ($reply == "ok") ? $count+1 : $count;
                
                $response[$itemId] = array(
                    'success' => ($reply == "ok") ? true : false,
                    'status' => ($result[$itemId]['success'])
                        ? "hold_cancel_success" : "hold_cancel_fail",
                    'sysMessage' => ($reply == "ok") ? false : $reply,
                );
                
            } else {
                $response[$itemId] = array(
                    'success' => false, 'status' => "hold_cancel_fail"
                );
            }
        }
        $result = array('count' => $count, 'items' => $response);
        return $result;
    }

    /**
     * Get Cancel Hold Details
     *
     * In order to cancel a hold, Voyager requires the patron details an item ID
     * and a recall ID. This function returns the item id and recall id as a string
     * separated by a pipe, which is then submitted as form data in Hold.php. This
     * value is then extracted by the CancelHolds function.
     *
     * @param array $holdDetails An array of item data
     *
     * @return string Data for use in a form field
     * @access public
     */
    public function getCancelHoldDetails($holdDetails)
    {
        $cancelDetails = $holdDetails['item_id']."|".$holdDetails['reqnum'];
        return $cancelDetails;
    }

    /**
     * Get Renew Details
     *
     * In order to renew an item, Voyager requires the patron details and an item
     * id. This function returns the item id as a string which is then used
     * as submitted form data in checkedOut.php. This value is then extracted by
     * the RenewMyItems function.
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     * @access public
     */
    public function getRenewDetails($checkOutDetails)
    {
        $renewDetails = $checkOutDetails['item_id'];
        return $renewDetails;
    }
}

?>
