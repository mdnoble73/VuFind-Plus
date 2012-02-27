<?php
/**
 * Horizon ILS Driver
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
 * @author   Matt Mackey <vufind-tech@lists.sourceforge.net>
 * @author   Ray Cummins <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
require_once 'Horizon.php';
require_once 'sys/VuFindDate.php';

/**
 * Horizon ILS Driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Matt Mackey <vufind-tech@lists.sourceforge.net>
 * @author   Ray Cummins <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
class HorizonXMLAPI extends Horizon
{

    /**
     * Constructor
     *
     * @param string $configFile An alternative config file name
     *
     * @access public
     */
    function __construct($configFile = 'HorizonXMLAPI.ini')
    {
        // Call the parent's constructor...
        parent::__construct($configFile);

        // Process Config
        $this->wsProfile = $this->config['Webservices']['profile'];
        $this->wsURL = $this->config['Webservices']['HIPurl'];
        $this->wsPickUpLocations
            = (isset($this->config['pickUpLocations']))
            ? $this->config['pickUpLocations'] : false;

        $this->wsDefaultPickUpLocation
            = (isset($this->config['Holds']['defaultPickUpLocation']))
            ? $this->config['Holds']['defaultPickUpLocation'] : false;

        // Set up object for formatting dates and times:
        $this->dateFormat = new VuFindDate();
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
        global $configArray;

        if (isset($this->config[$function]) ) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
        return $functionConfig;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param string $id     Bib Id
     * @param array  $row    SQL Row Data
     * @param array  $patron Patron Array
     *
     * @return array Keyed data
     * @access protected
     */
    protected function processHoldingRow($id, $row, $patron)
    {
        $holding = parent::processHoldingRow($id, $row, $patron);
        $holding += array(
            'item_id' => $id,
            'addLink' => $this->_checkItemRequests($patron, $id)
        );
        return $holding;
    }

    /**
     * Protected support method for getMyHolds.
     *
     * @param array $row An sql row
     *
     * @return array Keyed data
     * @access protected
     */
    protected function processHoldsRow($row)
    {
        $hold = parent::processHoldsRow($row);
        // item# not populated by Horizon,
        // this is provided for VuFind matching
        if ($hold) {
            $hold['item_id'] = $row['BIB_NUM'];
        }
        return $hold;
    }

    /**
     * Determine Renewability
     *
     * This is responsible for determining if an item is renewable
     *
     * @param string $requested The number of times an item has been requested
     *
     * @return array $renewData Array of the renewability status and associated
     * message
     * @access private
     */

    private function _determineRenewability($requested)
    {
        $renewData = array();

        $renewData['renewable'] = ($requested == 0) ? true : false;

        if (!$renewData['renewable']) {
            $renewData['message'] = "renew_item_requested";
        } else {
            $renewData['message'] = false;
        }

        return $renewData;
    }

    /**
     * Protected support method for getMyTransactions.
     *
     * @param array $row An array of keyed data
     *
     * @return array Keyed data for display by template files
     * @access protected
     */
    protected function processTransactionRow($row)
    {
        $transactions = parent::processTransactionsRow($row);
        $renewData = $this->_determineRenewability($row['REQUEST']);
        $transactions += array(
            'renewable' => $renewData['renewable'],
            'message' => $renewData['message']
        );
        return $transactions;
    }

    /* Horizon XML API Functions */

    /**
     * Get Pick Up Locations
     *
     * This is responsible get a list of valid library locations for holds / recall
     * retrieval
     *
     * @param array $patron The Patron's ID
     *
     * @return array        An keyed array where libray id => Library Display Name
     * @access public
     */
    public function getPickUpLocations($patron)
    {
        // It is not possible to get a list of pick up locations without supplying
        // a valid bibliographic id - In order to provide pick up locations for
        // "My Profile", we must rely on values from the config file
        $pickresponse = false;
        if (isset($this->wsPickUpLocations)) {
            foreach ($this->wsPickUpLocations as $code => $library) {
                $pickresponse[] = array('locationID' => $code,
                                        'locationDisplay' => $library);
            }
        }
        return $pickresponse;
    }

    /**
     * Get Default Pick Up Location
     *
     * Returns the default pick up location set in HorizonXMLAPI.ini
     *
     * @param array $patron Patron information returned by the patronLogin method.
     *
     * @return string A location ID
     * @access public
     */
    public function getDefaultPickUpLocation($patron = false)
    {
        return $this->wsDefaultPickUpLocation;
    }

    /**
     * Make Request
     *
     * Makes a request to the Horizon API
     *
     * @param array  $params A keyed array of query data
     * @param string $mode   The http request method to use (Default of GET)
     *
     * @return obj  A Simple XML Object loaded with the xml data returned by the API
     * @access private
     */

    private function _makeRequest($params = false, $mode = "GET")
    {
        // Build Url Base
        $urlParams = $this->wsURL;

        // Add Params
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                foreach ($param as $sub) {
                    $queryString[] = $key. "=" .urlencode($sub);
                }
            } else {
                // This is necessary as Horizon expects spaces to be represented by
                // "+" rather than the url_encode "%20" for Pick Up Locations
                $queryString[] = $key. "=" .
                    str_replace("%20", "+", urlencode($param));
            }
        }

        // Build Params
        $urlParams .= "?" . implode("&", $queryString);

        // Create Proxy Request
        $client = new Proxy_Request($urlParams, array('useBrackets' => false));

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
     *  Get Session
     *
     * Gets a Horizon session
     *
     * @return mixed A session string on success, boolean false on failure
     * @access private
     */

    private function _getSession()
    {
        $params = array("profile" => $this->wsProfile,
                        "menu" => "account",
                        "GetXML" => "true"
                        );

        $response = $this->_makeRequest($params);

        if ($response && $response->session) {
            $session = (string)$response->session;
            return $session;
        }

        return false;
    }

    /**
     *  Register User
     *
     * Associates a user with a session
     *
     * @param string $userBarcode  A valid Horizon user barcode
     * @param string $userPassword A valid Horizon user password (pin)
     *
     * @return boolean true on success, false on failure
     * @access private
     */

    private function _registerUser($userBarcode, $userPassword)
    {
        // Get Session
        $session = $this->_getSession();

        $params = array("session" => $session,
                        "profile" => $this->wsProfile,
                        "menu" => "account",
                        "sec1" => $userBarcode,
                        "sec2" => $userPassword,
                        "GetXML" => "true"
                        );

        $response = $this->_makeRequest($params);

        $auth = (string)$response->security->auth;

        if ($auth == "true") {
            return $session;
        }

        return false;
    }

    /**
     * Check Item Requests
     *
     * Determines if a user can place a hold or recall on a specific item
     *
     * @param array  $patron Patron Array Data
     * @param string $bibId  An item's Bib ID
     * @param string $itemId An item's Item ID (optional)
     *
     * @return boolean true if the request can be made, false if it cannot
     * @access private
     */
    private function _checkItemRequests($patron, $bibId, $itemId = false)
    {
        // Register Account
        $session = $this->_registerUser(
            $patron['cat_username'], $patron['cat_password']
        );
        if ($session) {
            $params = array(
                "session" => $session,
                "profile" => $this->wsProfile,
                "bibkey" => $bibId,
                "aspect" => "submenu13",
                "lang" => "eng",
                "menu" => "request",
                "submenu" => "none",
                "source" => "~!horizon",
                "uri" => "",
                "GetXML" => "true"
            );

            $initResponse = $this->_makeRequest($params);

            if ($initResponse->request_confirm) {
                return "Request";
            }
        }
        return false;
    }

    /**
     *  Get Items
     *
     * Gets a list of items on loan
     *
     * @param string $session A valid Horizon session key
     *
     * @return obj A Simple XML Object
     * @access private
     */

    private function _getItems($session)
    {
        $params = array("session" => $session,
                        "profile" => $this->wsProfile,
                        "menu" => "account",
                        "submenu" => "itemsout",
                        "GetXML" => "true"
                        );

        $response = $this->_makeRequest($params);

        if ($response->itemsoutdata) {
            return $response->itemsoutdata;
        }

        return false;
    }

    /**
     *  Renew Items
     *
     * Submits a renewal request to the Horizon API and returns the results
     *
     * @param string $session A valid Horizon session key
     * @param array  $items   A list of items to be renewed
     *
     * @return obj A Simple XML Object
     * @access private
     */

    private function _renewItems($session, $items)
    {
        $params = array("session" => $session,
                        "profile" => $this->wsProfile,
                        "menu" => "account",
                        "submenu" => "itemsout",
                        "renewitemkeys" => $items,
                        "renewitems" => "Renew",
                        "GetXML" => "true"
                        );

        $response = $this->_makeRequest($params);

        if ($response->itemsoutdata) {
            return $response->itemsoutdata;
        }

        return false;
    }

    /**
     * Place Request
     *
     * Submits a hold request to the Horizon XML API and processes the result
     *
     * @param string $session        A valid Horizon session key
     * @param array  $requestDetails An array of request details
     *
     * @return array  An array witk keys indicating the a success (boolean),
     * status (string) and sysMessage (string) if available
     * @access private
     */

    private function _placeRequest($session, $requestDetails)
    {
        $params = array("session" => $session,
                        "profile" => $this->wsProfile,
                        "bibkey" => $requestDetails['bibId'],
                        "aspect" => "submenu13",
                        "lang" => "eng",
                        "menu" => "request",
                        "submenu" => "none",
                        "source" => "~!horizon",
                        "uri" => "",
                        "GetXML" => "true"
                        );

        $initResponse = $this->_makeRequest($params);

        if ($initResponse->request_confirm) {

              $confirmParams =  array("session" => $session,
                        "profile" => $this->wsProfile,
                        "bibkey" => $requestDetails['bibId'],
                        "aspect" => "advanced",
                        "lang" => "eng",
                        "menu" => "request",
                        "submenu" => "none",
                        "source" => "~!horizon",
                        "uri" => "",
                        "link" => "direct",
                        "request_finish" => "Request",
                        "cl" => "PlaceRequestjsp",
                        "pickuplocation" => $requestDetails['pickuplocation'],
                        "notifyby" => $requestDetails['notify'],
                        "GetXML" => "true"
                        );

            $request = $this->_makeRequest($confirmParams);

            if ($request->request_success) {
                $response = array(
                    'success' => true,
                    'status' => "hold_success"
                );
            } else {
                $response = array(
                    'success' => false,
                    'status' => "hold_error_fail"
                );
            }
        } else {
            $sysMessage = false;
            if ($initResponse->alert->message) {
                $sysMessage = (string)$initResponse->alert->message;
            }
            $response = array(
                'success' => false,
                'status' => "hold_error_fail",
                'sysMessage' => $sysMessage
            );
        }
        return $response;
    }

    /**
     * Cancel Request
     *
     * Submits a cancel request to the Horizon API and processes the result
     *
     * @param string $session A valid Horizon session key
     * @param Array  $data    An array of item data
     *
     * @return array  An array of cancel information keyed by item ID plus
     * the number of successful cancels
     * @access private
     */

    private function _cancelRequest($session, $data)
    {
        $responseItems = array();
        
        foreach ($data as $values) {
            $bibData[] = $values['bib_id'];
            $items[] = $values['item_id'];
        }

        $params = array("session" => $session,
                        "profile" => $this->wsProfile,
                        "lang" => "eng",
                        "menu" => "account",
                        "submenu" => "holds",
                        "cancelhold" => "Cancel Request",
                        "waitingholdselected" => $bibData,
                        "GetXML" => "true"
                        );

        $response = $this->_makeRequest($params);

        // No Indication of Success or Failure
        if ($response !== false && !$response->error->message) {

            $keys = array();
            // Get a list of bib keys from waiting items
            $currentHolds = $response->holdsdata->waiting->waitingitem;
            foreach ($currentHolds as $hold) {
                foreach ($hold->key as $key) {
                    $keys[] = (string)$key;
                }
            }

            $count = 0;
            // Go through the submited bib ids and look for a match
            foreach ($data as $values) {
                $bibID = $values['bib_id'];
                $itemID = $values['item_id'];
                // If the bib id is matched, the cancel must have failed
                if (in_array($values['bib_id'], $keys)) {
                    $responseItems[$itemID] = array(
                        'success' => false, 'status' => "hold_cancel_fail"
                    );
                } else {
                    $responseItems[$itemID] = array(
                        'success' => true, 'status' => "hold_cancel_success",

                    );
                    $count = $count+1;
                }
            }
        } else {
            $message = false;
            if ($response->error->message) {
                $message = (string)$response->error->message;
            }
            foreach ($items as $itemID) {
                $responseItems[$itemID] = array(
                    'success' => false,
                    'status' => "hold_cancel_fail",
                    'sysMessage' => $message
                );
            }
        }
        $result = array('count' => $count, 'items' => $responseItems);
        return $result;
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return array  An array of data on the request including
     * whether or not it was successful and a system message (if available)
     * @access public
     */
    public function placeHold($holdDetails)
    {
        $userId = $holdDetails['patron']['id'];
        $userBarcode = $holdDetails['patron']['id'];
        $userPassword = $holdDetails['patron']['cat_password'];
        $bibId = $holdDetails['id'];
        $pickUpLocationID = !empty($holdDetails['pickUpLocation'])
            ? $holdDetails['pickUpLocation']
            : $this->getDefaultPickUpLocation();
        $pickUpLocation = trim($this->config['pickUpLocations'][$pickUpLocationID]);
        $notify = $this->config['Holds']['notify'];

        $requestDetails = array(
            'bibId' => $bibId,
            'pickuplocation' => $pickUpLocation,
            'notify' => $notify
        );

        // Register Account
        $session = $this->_registerUser($userBarcode, $userPassword);
        if ($session) {
            $response = $this->_placeRequest($session, $requestDetails);
        } else {
            $response = array(
                'success' => false, 'status' => "authentication_error_admin"
            );
        }

        return $response;
    }

    /**
     * Cancel Holds
     *
     * Attempts to Cancel a hold on a particular item
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return mixed  An array of data on each request including
     * whether or not it was successful and a system message (if available)
     * or boolean false on failure
     * @access public
     */

    public function cancelHolds($cancelDetails)
    {
        $details = $cancelDetails['details'];
        $userBarcode = $cancelDetails['patron']['id'];
        $userPassword = $cancelDetails['patron']['cat_password'];

        foreach ($details as $cancelItem) {
            list($bibID, $itemID) = explode("|", $cancelItem);
            $cancelIDs[]  = array("bib_id" =>  $bibID, "item_id" => $itemID);
        }

        // Register Account
        $session = $this->_registerUser($userBarcode, $userPassword);
        if ($session) {
            $response = $this->_cancelRequest($session, $cancelIDs);
        } else {
            $response = array(
                'success' => false, 'sysMessage' => "authentication_error_admin"
            );
        }
        return $response;
    }

    /**
     * Process Renewals
     *
     * This is responsible for processing renewals and is neccessary
     * as result of renew attempt is not returned
     *
     * @param array $renewIDs  A list of the items being renewed
     * @param array $origData  A Simple XML array of loan data before the
     * renewal attempt
     * @param array $renewData A Simple XML array of loan data after the
     * renewal attempt
     *
     * @return array        An Array specifiying the results of each renewal attempt
     * @access private
     */
    private function _processRenewals($renewIDs, $origData, $renewData)
    {
        $response['ids'] = $renewIDs;
        $i = 0;
        foreach ($origData->itemout as $item) {

            $ikey = (string)$item->ikey;
            if (in_array($ikey, $renewIDs)) {

                $response['details'][$ikey]['item_id'] = $ikey;
                $origRenewals = (string)$item->numrenewals;
                $currentRenewals = (string)$renewData->itemout[$i]->numrenewals;

                $dueDate = (string)$renewData->itemout[$i]->duedate;

                // Convert Horizon Format to display format
                if (!empty($dueDate)) {
                    $currentDueDate = $this->dateFormat->convertToDisplayDate(
                        "d/m/Y", $dueDate
                    );
                    if (PEAR::isError($dueDate)) {
                        return $currentDueDate;
                    }
                }

                if ($currentRenewals > $origRenewals) {

                    $response['details'][$ikey] = array(
                        'item_id' => $ikey,
                        'new_date' =>  $currentDueDate,
                        'success' => true
                    );

                } else {
                    $response['details'][$ikey] = array(
                    'item_id' => $ikey,
                    'new_date' => "",
                    'success' => false
                    );
                }
            }
            $i++;
        }
        return $response;
    }

    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS and barcodes
     *
     * @return mixed  An array of renewal information keyed by item ID on success
     * and a boolean false on failure
     * @access public
     */

    public function renewMyItems($renewDetails)
    {
        $renewals = $renewDetails['details'];
        $patron = $renewDetails['patron'];
        $renewals_count = count($renewals);
        $userId = $holdDetails['patron']['id'];
        $userBarcode = $renewDetails['patron']['id'];
        $userPassword = $renewDetails['patron']['cat_password'];

        $session = $this->_registerUser($userBarcode, $userPassword);
        if ($session) {
            // Get Items
            $origData = $this->_getItems($session);
            if ($origData) {
                // Build Params
                foreach ($renewals as $item) {
                    list($itemID, $barcode) = explode("|", $item);
                    $renewItemKeys[] = $barcode;
                    $renewIDs[] = $itemID;
                }
                // Renew Items
                $renewData = $this->_renewItems($session, $renewItemKeys);
                if ($renewData) {
                    $response = $this->_processRenewals(
                        $renewIDs, $origData, $renewData
                    );
                    return $response;
                }
            }
        }

        return array('block' => "authentication_error_admin");
    }

    /**
     * Get Renew Details
     *
     * In order to renew an item, Horizon requires the patron details an item
     * id and a barcode. This function returns the item id and barcode as a string
     * which is then used as submitted form data in checkedOut.php. This value is
     * then extracted by the RenewMyItems function.
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     * @access public
     */
    public function getRenewDetails($checkOutDetails)
    {
        $renewDetails = $checkOutDetails['item_id']."|".$checkOutDetails['barcode'];
        return $renewDetails;
    }

    /**
     * Get Cancel Hold Form
     *
     * Supplies the form details required to cancel a hold
     *
     * @param array $holdDetails An array of item data
     *
     * @return string  Data for use in a form field
     * @access public
     */

    public function getCancelHoldDetails($holdDetails)
    {
        $cancelDetails = $holdDetails['id']."|".$holdDetails['item_id'];
        return $cancelDetails;
    }
}

?>