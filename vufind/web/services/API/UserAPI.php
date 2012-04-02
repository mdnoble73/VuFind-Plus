<?php
require_once 'Action.php';
require_once 'CatalogConnection.php';

/**
 * API methods related to getting User information from VuFind from external programs.
 * Also handles account actions like placing holds, cancelling holds, etc.
 *
 * Copyright (C) Douglas County Libraries 2011.
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
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class UserAPI extends Action {

	/*
	 * Stores the connection to the ILS
	 *
	 * @internal
	 * @access private
	 */
	private $catalog;

	/**
	 * Processes method to determine return type and calls the correct method.
	 * Should not be called directly.
	 *
	 * @see Action::launch()
	 * @access private
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function launch()
	{
		global $configArray;
		// Connect to Catalog
		$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);

		//header('Content-type: application/json');
		header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (is_callable(array($this, $_GET['method']))) {
			$output = json_encode(array('result'=>$this->$_GET['method']()));
		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}

		echo $output;
	}

	/**
	 *
	 * Returns whether or not a user is currently logged in based on session information.
	 * This method is only useful from VuFind itself or from files which can share cookies
	 * with the VuFind server.
	 *
	 * Returns:
	 * <code>
	 * {result:[true|false]}
	 * </code>
	 *
	 * Sample call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=isLoggedIn
	 * </code>
	 *
	 * Sample response:
	 * <code>
	 * {"result":true}
	 * </code>
	 *
	 * @access private
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function isLoggedIn(){
		$user = UserAccount::isLoggedIn();
		if ($user != false && !PEAR::isError($user)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Logs in the user and sets a cookie indicating that the user is logged in.
	 * Must be called by POSTing data to the API.
	 * This method is only useful from VuFind itself or from files which can share cookies
	 * with the VuFind server.
	 *
	 * Sample call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI
	 * Post variables:
	 *   method=login
	 *   username=23025003575917
	 *   password=7604
	 * </code>
	 *
	 * Sample response:
	 * <code>
	 * {"result":true}
	 * </code>
	 *
	 * @access private
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function login(){
		//Login the user.  Must be called via Post parameters.
		$user = UserAccount::isLoggedIn();
		if (isset($_POST['username']) && isset($_POST['password'])){
			if ($user && !PEAR::isError($user)){
				return array('success'=>true,'name'=>ucwords($user->firstname . ' ' . $user->lastname));
			}else{
				$user = UserAccount::login();
				if ($user && !PEAR::isError($user)){
					return array('success'=>true,'name'=>ucwords($user->firstname . ' ' . $user->lastname));
				}else{
					return array('success'=>false);
				}
			}
		}else{
			return array('success'=>false,'message'=>'This method must be called via POST.');
		}
	}

	/**
	 * Logs the user out of the system and clears cookies indicating that the user is logged in.
	 * This method is only useful from VuFind itself or from files which can share cookies
	 * with the VuFind server.
	 *
	 * Sample call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=logout
	 * </code>
	 *
	 * Sample response:
	 * <code>
	 * {"result":true}
	 * </code>
	 *
	 * @access private
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function logout(){
		UserAccount::logout();
		return true;
	}

	/**
	 * Validate whether or not an account is valid based on the barcode and pin number provided.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user.
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success - false if the username or password could not be found, or the folowing user information if the account is valid.</li>
	 * <li>id – The id of the user within VuFind</li>
	 * <li>username, cat_username – The patron's library card number</li>
	 * <li>password, cat_password – The patron's PIN number</li>
	 * <li>firstname – The first name of the patron in the ILS</li>
	 * <li>lastname – The last name of the patron in the ILS</li>
	 * <li>email – The patron's e-mail address if set within Horizon.</li>
	 * <li>college, major – not currently used</li>
	 * <li>homeLocationId – the id of the patron's home libarary within VuFind.</li>
	 * <li>MyLocation1Id, myLocation2Id – not currently used</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=validateAccount&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":{
	 *     "id":"5",
	 *     "username":"23025003575917",
	 *     "password":"7604",
	 *     "firstname":"OS test 1",
	 *     "lastname":"",
	 *     "email":"email",
	 *     "cat_username":"23025003575917",
	 *     "cat_password":"7604",
	 *     "college":"null",
	 *     "major":"null",
	 *     "homeLocationId":null,
	 *     "myLocation1Id":null,
	 *     "myLocation2Id":null
	 *     }
	 *   }
	 * }
	 * </code>
	 *
	 * Sample Response failed login:
	 * <code>
	 * {"result":{"success":false}}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function validateAccount(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$result = UserAccount::validateAccount($username, $password);
		if ($result != null){
			//get rid of data object fields before returning the result
			unset($result->__table);
			unset($result->created);
			unset($result->_DB_DataObject_version);
			unset($result->_database_dsn);
			unset($result->_database_dsn_md5);
			unset($result->_database);
			unset($result->_query);
			unset($result->_DB_resultid);
			unset($result->_resultFields);
			unset($result->_link_loaded);
			unset($result->_join);
			unset($result->_lastError);
			unset($result->N);

			return array('success'=>$result);
		}else{
			return array('success'=>false);
		}
	}

	/**
	 * Load patron profile information for a user based on username and password.
	 * Includes information about print titles and eContent titles that the user has checked out.
	 * Does not include information about OverDrive titles since tat
	 *
	 * Usage:
	 * <code>
	 * {siteUrl}/API/UserAPI?method=getPatronProfile&username=patronBarcode&password=pin
	 * </code>
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid, false if the username or password were incorrect</li>
	 * <li>message – a reason why the method failed if success is false</li>
	 * <li>profile – profile information including name, address, e-mail, number of holds, number of checked out items, fines.</li>
	 * <li>firstname – The first name of the patron in the ILS</li>
	 * <li>lastname – The last name of the patron in the ILS</li>
	 * <li>fullname – The combined first and last name for the patron in the ILS</li>
	 * <li>address1 – The street information for the patron</li>
	 * <li>city – The city where the patron lives</li>
	 * <li>state – The state where the patron lives</li>
	 * <li>zip – The zip code for the patron</li>
	 * <li>phone – The phone number for the patron</li>
	 * <li>email – The email for the patron</li>
	 * <li>homeLocationId – The id of the patron's home branch within VuFind</li>
	 * <li>homeLocationName – The full name of the patron's home branch</li>
	 * <li>expires – The expiration date of the patron's library card</li>
	 * <li>fines – the amount of fines on the patron's account formatted for display</li>
	 * <li>finesVal – the amount of  fines on the patron's account without formatting</li>
	 * <li>numHolds – The number of holds the patron currently has</li>
	 * <li>numHoldsAvailable – The number of holds the patron currently has that are available</li>
	 * <li>numHoldsRequested – The number of holds the patron currently has that are not available</li>
	 * <li>numCheckedOut – The number of items the patron currently has checked out.</li>
	 * <li>bypassAutoLogout - 1 if the user has chosen to bypass te automatic logout script or 0 if they have not.</li>
	 * <li>numEContentCheckedOut - The number of eContent items that the user currently has checked out. </li>
	 * <li>numEContentAvailableHolds - The number of available eContent holds for the user that can be checked out. </li>
	 * <li>numEContentUnavailableHolds - The number of unavailable eContent holds for the user.</li>
	 * <li>numEContentWishList - The number of eContent titles the user has added to their wishlist.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronProfile&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response failed login:
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "message":"Login unsuccessful"
	 * }}
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * { "result" : { "profile" : {
	 *   "address1" : "P O BOX 283",
	 *   "bypassAutoLogout" : "0",
	 *   "city" : "LOUVIERS",
	 *   "displayName" : "",
	 *   "email" : "test@comcast.net",
	 *   "expires" : "02/03/2039",
	 *   "fines" : 0,
	 *   "finesval" : "",
	 *   "firstname" : "",
	 *   "fullname" : "POS test 1",
	 *   "homeLocationId" : "3",
	 *   "homeLocationName" : "Philip S. Miller",
	 *   "lastname" : "POS test 1",
	 *    "numCheckedOut" : 0,
	 *   "numEContentAvailableHolds" : 0,
	 *   "numEContentCheckedOut" : 0,
	 *   "numEContentUnavailableHolds" : 0,
	 *   "numEContentWishList" : 0,
	 *   "numHolds" : 0,
	 *   "numHoldsAvailable" : 0,
	 *   "numHoldsRequested" : 0,
	 *   "phone" : "303-555-5555",
	 *   "state" : "CO",
	 *   "zip" : "80131"
	 * },
	 * "success" : true
	 * } }
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronProfile(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$profile = $this->catalog->getMyProfile($user);
			return array('success'=>true, 'profile'=>$profile);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get eContent and ILS holds for a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>includeEContent - Optional flag for whether or not to include eContent holds. Set to false to only include print titles.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid, false if the username or password were incorrect</li>
	 * <li>message – a reason why the method failed if success is false</li>
	 * <li>holds – information about each hold including when it was placed, when it expires, and whether or not it is available for pickup.  Holds are broken into two sections: available and unavailable.  Available holds are ready for pickup.</li>
	 * <li>Id – the record/bib id of the title being held</li>
	 * <li>location – The location where the title will be picked up</li>
	 * <li>expire – the date the hold will expire if it is unavailable or the date that it must be picked up if the hold is available</li>
	 * <li>expireTime – the expire information in number of days since January 1, 1970 </li>
	 * <li>create – the date the hold was originally placed</li>
	 * <li>createTime – the create information in number of days since January 1, 1970</li>
	 * <li>reactivate – The date the hold will be reactivated if the hold is suspended</li>
	 * <li>reactivateTime – the reactivate information in number of days since January 1, 1970</li>
	 * <li>available – whether or not the hold is available for pickup</li>
	 * <li>position – the patron's position in the hold queue</li>
	 * <li>frozen – whether or not the hold is frozen</li>
	 * <li>itemId – the barcode of the item that filled the hold if the hold has been filled.</li>
	 * <li>Status – a textual status of the item (Available, Suspended, Active, In Transit)</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronHolds&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * { "result" :
	 *   { "holds" :
	 *     { "unavailable" : [
	 *       { "author" : "Bernhardt, Gale, 1958-",
	 *            "available" : false,
	 *            "availableTime" : null,
	 *            "barcode" : "33025016545293",
	 *            "create" : "2011-12-20 00:00:00",
	 *            "createTime" : 15328,
	 *            "expire" : "[No expiration date]",
	 *            "expireTime" : null,
	 *            "format" : "Book",
	 *            "format_category" : [ "Books" ],
	 *            "frozen" : false,
	 *            "id" : 868679,
	 *            "isbn" : [ "1931382921 (paper)",
	 *                "9781931382922"
	 *              ],
	 *            "itemId" : 1559061,
	 *            "location" : "Parker",
	 *            "position" : 1,
	 *            "reactivate" : "",
	 *            "reactivateTime" : null,
	 *            "sortTitle" : "training plans for multisport athletes",
	 *            "status" : "In Transit",
	 *            "title" : "Training plans for multisport athletes /",
	 *            "upc" : ""
	 *       } ]
	 *     },
	 *     { "available" : [
	 *       { "author" : "Hunter, Erin.",
	 *            "available" : true,
	 *            "availableTime" : null,
	 *            "barcode" : "33025025084185",
	 *            "create" : "2011-09-27 00:00:00",
	 *            "createTime" : 15244,
	 *            "expire" : "2012-01-09 00:00:00",
	 *            "expireTime" : 15348,
	 *            "format" : "Book",
	 *            "format_category" : [ "Books" ],
	 *            "frozen" : false,
	 *            "id" : 1012238,
	 *            "isbn" : [ "9780061555220",
	 *                "0061555223"
	 *              ],
	 *            "itemId" : 2216202,
	 *            "location" : "Parker",
	 *            "position" : 2,
	 *            "reactivate" : "",
	 *            "reactivateTime" : 15308,
	 *            "sortTitle" : "forgotten warrior",
	 *            "status" : "Available",
	 *            "title" : "The forgotten warrior /",
	 *            "upc" : ""
	 *          } ]
	 *     },
	 *     "success" : true
	 *  }
	 * }
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronHolds(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$includeEContent = true;
		if (isset($_REQUEST['includeEContent'])){
			$includeEContent = $_REQUEST['includeEContent'];
		}


		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$patronHolds = $this->catalog->getMyHolds($user);
			if ($includeEContent === true || $includeEContent === 'true'){
				require_once('Drivers/EContentDriver.php');
				$eContentDriver = new EContentDriver();
				$eContentHolds = $eContentDriver->getMyHolds($user);
				$allHolds = array_merge_recursive($eContentHolds['holds'], $patronHolds['holds']);
			}else{
				$allHolds = $patronHolds['holds'];
			}
			return array('success'=>true, 'holds'=>$allHolds);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get eContent holds for a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronHoldsEContent&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holds":{
	 *     "available":[
	 *       {"id":"522",
	 *        "source":"CIPA",
	 *        "title":"Into the path of gods",
	 *        "author":"Guler, Kathleen Cunningham.",
	 *        "available":true,
	 *        "create":"1325962832",
	 *        "expire":1326394839,
	 *        "status":"available",
	 *        "links":[
	 *          {"text":
	 *           "Cancel Hold",
	 *           "onclick":"if (confirm('Are you sure you want to cancel this title?')){cancelEContentHold('\/EContentRecord\/522\/CancelHold')};return false;"
	 *          },
	 *          {"text":"Checkout",
	 *           "url":"\/EContentRecord\/522\/Checkout"
	 *          }
	 *        ]
	 *       }
	 *     ],
	 *     "unavailable":[
	 *       {"id":"521",
	 *        "source":"CIPA",
	 *        "title":"Turn eye appeal into buy appeal how to easily transform your marketing pieces into dazzling, persuasive sales tools! \/",
	 *        "author":"Saunders, Karen.",
	 *        "available":true,
	 *        "createTime":"1325962794",
	 *        "status":"active",
	 *        "position":1,
	 *        "links":[
	 *          {"text":"Cancel Hold",
	 *           "onclick":"if (confirm('Are you sure you want to cancel this title?')){cancelEContentHold('\/EContentRecord\/521\/CancelHold')};return false;"
	 *          }
	 *        ],
	 *        "frozen":false,
	 *        "reactivateDate":null
	 *       }
	 *     ]
	 *   }
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronHoldsEContent(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$eContentDriver = new EContentDriver();
			$eContentHolds = $eContentDriver->getMyHolds($user);
			return array('success'=>true, 'holds'=>$eContentHolds['holds']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get a list of holds with details from OverDrive.
	 * Note: OverDrive can be very slow at times.  Proper precautions should be taken to ensure the calling application
	 * remains responsive.  VuFind does handle caching of OverDrive details so additional caching should not be needed.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronHoldsOverDrive&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holds":{
	 *     "available":[{
	 *       "overDriveId":"2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *       "imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/1523-1\/%7B2C32E00B-8838-4F2A-BED7-EAEF2E9249C8%7DImg200.jpg",
	 *       "title":"Danger in a Red Dress",
	 *       "subTitle":"The Fortune Hunter Series, Book 4",
	 *       "author":"Christina Dodd",
	 *       "recordId":"9604",
	 *       "notificationDate":1325921790,
	 *       "expirationDate":1326180990,
	 *       "formats":[
	 *         {"name":"Kindle Book",
	 *          "overDriveId":
	 *          "2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *          "formatId":"420"
	 *         },
	 *         {"name":"Adobe EPUB eBook",
	 *          "overDriveId":"2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *          "formatId":"410"
	 *         },
	 *         {"name":"Adobe PDF eBook",
	 *          "overDriveId":"2C32E00B-8838-4F2A-BED7-EAEF2E9249C8",
	 *          "formatId":"50"
	 *         }
	 *       ]
	 *     }],
	 *     "unavailable":[{
	 *       "overDriveId":"E750E1B6-2B11-42B5-89CB-153A286FB4A0",
	 *       "imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/0293-1\/%7BE750E1B6-2B11-42B5-89CB-153A286FB4A0%7DImg200.jpg",
	 *       "title":"Sunrise",
	 *       "subTitle":"Warriors: Power of Three Series, Book 6",
	 *       "author":"Erin Hunter",
	 *       "recordId":"7356",
	 *       "formatId":"410",
	 *       "holdQueuePosition":"1",
	 *       "holdQueueLength":"1"
	 *     }]
	 *   }
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronHoldsOverDrive(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$eContentDriver = new OverDriveDriver();
			$eContentHolds = $eContentDriver->getOverDriveHolds($user);
			return array('success'=>true, 'holds'=>$eContentHolds['holds']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get a list of items in the user's OverDrive cart.
	 * For patrons tha use OverDrive exclusively with VuFind this method will never content because
	 * the cart is always empty except during the checkout process.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronCartOverDrive&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true,"items":[]}}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronCartOverDrive(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$eContentDriver = new OverDriveDriver();
			$eContentCartItems = $eContentDriver->getOverDriveCart($user);
			return array('success'=>true, 'items'=>$eContentCartItems['items']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get a list of items in the user's OverDrive wishlist.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronWishListOverDrive&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "items":[
	 *     {"overDriveId":"47BCED53-7AEC-47A2-A5D9-75D493196095",
	 *      "imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/1191-1\/%7B47BCED53-7AEC-47A2-A5D9-75D493196095%7DImg200.jpg",
	 *      "title":"The Return of Depression Economics and the Crisis of 2008",
	 *      "subTitle":"",
	 *      "author":"Paul Krugman",
	 *      "dateAdded":"Apr 19, 2009\r\n",
	 *      "formats":[
	 *        {"name":"OverDrive WMA Audiobook",
	 *         "available":true,
	 *         "formatId":"25"
	 *        }
	 *      ],
	 *      "recordId":"13233"
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronWishListOverDrive(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$eContentDriver = new OverDriveDriver();
			$eContentWishListItems = $eContentDriver->getOverDriveWishList($user);
			return array('success'=>true, 'items'=>$eContentWishListItems['items']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get a list of items that are currently checked out to the user within OverDrive.
	 * Note: VuFind takes care of caching the checked out items page appropriately.  The caling application should not
	 * do additional caching.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronCheckedOutItemsOverDrive&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "items":[
	 *     {"imageUrl":"http:\/\/images.contentreserve.com\/ImageType-200\/1138-1\/%7BA10890DB-DDEF-4BA5-BAEC-39AAF8A67D69%7DImg200.jpg",
	 *      "title":"An Object of Beauty",
	 *      "overDriveId":"A10890DB-DDEF-4BA5-BAEC-39AAF8A67D69",
	 *      "subTitle":"",
	 *      "format":"OverDrive WMA Audiobook ",
	 *      "downloadSize":"106251 kb",
	 *      "downloadLink":"http:\/\/ofs.contentreserve.com\/bin\/OFSGatewayModule.dll\/AnObjectofBeauty9781607889410.odm?RetailerID=douglascounty&Expires=1326047065&Token=a17a4a46-ea2a-4d2b-b076-79044d911a46&Signature=qCCvuYGCXS16C7TkMDg98%2fQU5YY%3d",
	 *      "checkedOutOn":"Jan 05, 2012",
	 *      "expiresOn":"Jan 12, 2012",
	 *      "recordId":"14955"
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronCheckedOutItemsOverDrive(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$eContentDriver = new OverDriveDriver();
			$eContentCheckedOutItems = $eContentDriver->getOverDriveCheckedOutItems($user);
			return array('success'=>true, 'items'=>$eContentCheckedOutItems['items']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get a count of items in various lists within overdrive (holds, cart, wishlist, checked out).
	 *
	 * Usage:
	 * <code>
	 * {siteUrl}/API/UserAPI?method=getPatronOverDriveSummary&username=patronBarcode&password=pin
	 * </code>
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronOverDriveSummary&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "summary":{
	 *     "numAvailableHolds":1,
	 *     "numUnavailableHolds":4,
	 *     "numCheckedOut":7,
	 *     "numWishlistItems":9
	 *   }
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronOverDriveSummary(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$eContentDriver = new OverDriveDriver();
			$overDriveSummary = $eContentDriver->getOverDriveSummary($user);
			return array('success'=>true, 'summary'=>$overDriveSummary);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get fines from the ILS for a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user.</li>
	 * <li>includeMessages - Whether or not messages to the user should be included within list of fines. (optional, defaults to false)</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronFines&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "fines":[
	 *     {"reason":"Privacy - Family permission",
	 *      "amount":"$0.00",
	 *      "message":"",
	 *      "date":"09\/27\/2005"
	 *     },
	 *     {"reason":"Charges Misc. Fees",
	 *      "amount":"$5.00",
	 *      "message":"",
	 *      "date":"04\/14\/2011"
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronFines(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$includeMessages = isset($_REQUEST['includeMessages']) ? $_REQUEST['includeMessages'] : false;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$fines = $this->catalog->getMyFines($user, $includeMessages);
			return array('success'=>true, 'fines'=>$fines);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get eContent and ILS records that are checked out to a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>includeEContent - Optional flag for whether or not to include checked out eContent. Set to false to only include print titles.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronCheckedOutItems&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "checkedOutItems":{
	 *     "0":{
	 *       "id":"534",
	 *       "source":"CIPA",
	 *       "title":"Unsinkable the Molly Brown story \/",
	 *       "author":"Lohse, Joyce B.",
	 *       "duedate":"1326135657",
	 *       "checkoutdate":"1325962857",
	 *       "daysUntilDue":2,
	 *       "holdQueueLength":0,
	 *       "links":[
	 *         {"url":"\/EContent\/534\/Viewer?item=130",
	 *          "text":"Read Online"
	 *         },
	 *         {"url":"http:\/\/fulfillment.douglascountylibraries.org\/fulfillment\/URLLink.acsm?action=enterloan&ordersource=DCL+Test&orderid=ACS4-1206216092244346610125819&resid=urn%3Auuid%3A130b9d63-5e4f-430c-aa16-8fb33822aba8&gbauthdate=Sat%2C+07+Jan+2012+19%3A00%3A58+%2B0000&dateval=1325962858&gblver=4&auth=8c6e70a135a7418c441a9b2b32b9ff6cb413e4cf",
	 *          "text":"Download"
	 *         },
	 *         {"text":"Return Now",
	 *          "onclick":"if (confirm('Are you sure you want to return this title?')){returnEpub('\/EContentRecord\/534\/ReturnTitle')};return false;"
	 *         }
	 *       ]
	 *     },
	 *     "33025021368319":{
	 *       "id":"966379",
	 *       "itemid":"33025021368319",
	 *       "duedate":"01\/24\/2012",
	 *       "checkoutdate":"2011-12-27 00:00:00",
	 *       "barcode":"33025021368319",
	 *       "renewCount":"1",
	 *       "request":null,
	 *       "overdue":false,
	 *       "daysUntilDue":16,
	 *       "title":"Be iron fit : time-efficient training secrets for ultimate fitness \/",
	 *       "sortTitle":"be iron fit : time-efficient training secrets for ultimate fitness \/ time-efficient training secrets for ultimate fitness \/",
	 *       "author":"Fink, Don.",
	 *       "format":"Book",
	 *       "isbn":"9781599218571"
	 *       ,"upc":"",
	 *       "format_category":"Books",
	 *       "holdQueueLength":3
	 *     }
	 *   }
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronCheckedOutItems(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$includeEContent = true;
		if (isset($_REQUEST['includeEContent'])){
			$includeEContent = $_REQUEST['includeEContent'];
		}
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$checkedOutItems = $this->catalog->getMyTransactions($user);
				
			if ($includeEContent === true || $includeEContent === 'true'){
				require_once('Drivers/EContentDriver.php');
				$eContentDriver = new EContentDriver();
				$eContentTransactions = $eContentDriver->getMyTransactions($user);

				$allTransactions = array_merge($eContentTransactions['transactions'], $checkedOutItems['transactions']);
			}else{
				$allTransactions = $checkedOutItems['transactions'];
			}
			return array('success'=>true, 'checkedOutItems'=>$allTransactions);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Get eContent records that are checked out to a user based on username and password.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronCheckedOutItems&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "checkedOutItems":[{
	 *     {"id":"534",
	 *      "source":"CIPA",
	 *      "title":"Unsinkable the Molly Brown story \/",
	 *      "author":"Lohse, Joyce B.",
	 *      "duedate":"1326135657",
	 *      "checkoutdate":"1325962857",
	 *      "daysUntilDue":2,
	 *      "holdQueueLength":0,
	 *      "links":[
	 *        {"url":"\/EContent\/534\/Viewer?item=130",
	 *         "text":"Read Online"
	 *        },
	 *        {"url":"http:\/\/fulfillment.douglascountylibraries.org\/fulfillment\/URLLink.acsm?action=enterloan&ordersource=DCL+Test&orderid=ACS4-1206216092244346610125819&resid=urn%3Auuid%3A130b9d63-5e4f-430c-aa16-8fb33822aba8&gbauthdate=Sat%2C+07+Jan+2012+19%3A00%3A58+%2B0000&dateval=1325962858&gblver=4&auth=8c6e70a135a7418c441a9b2b32b9ff6cb413e4cf",
	 *         "text":"Download"
	 *        },
	 *        {"text":"Return Now",
	 *         "onclick":"if (confirm('Are you sure you want to return this title?')){returnEpub('\/EContentRecord\/534\/ReturnTitle')};return false;"
	 *        }
	 *      ]
	 *     }
	 *   }]
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronCheckedOutEContent(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$eContentDriver = new EContentDriver();
			$eContentTransactions = $eContentDriver->getMyTransactions($user);
			$allTransactions = $eContentTransactions['transactions'];
			return array('success'=>true, 'checkedOutItems'=>$allTransactions);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Renews an item that has been checked out within the ILS.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>itemBarcode - The barcode of the item to be renewed.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=renewItem&username=23025003575917&password=7604&itemBarcode=33025021368319
	 * </code>
	 *
	 * Sample Response (failed renewal):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "renewalMessage":{
	 *     "itemId":"33025021368319",
	 *     "result":false,
	 *     "message":"This item may not be renewed - Item has been requested."
	 *   }
	 * }}
	 * </code>
	 *
	 * Sample Response (successful renewal):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "renewalMessage":{
	 *     "itemId":"33025021723869",
	 *     "result":true,
	 *     "message":"#Renewal successful."
	 *   }
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function renewItem(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$itemBarcode = $_REQUEST['itemBarcode'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$renewalMessage = $this->catalog->renewItem($user->cat_username, $itemBarcode);
			return array('success'=>true, 'renewalMessage'=>$renewalMessage);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Renews all items that have been checked out to the user from the ILS.
	 * Returns a count of the number of items that could be renewed.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=renewAll&username=23025003575917&password=7604
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "renewalMessage":"0006 of 8 items were renewed successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function renewAll(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$renewalMessage = $this->catalog->renewAll($user->cat_username);
			return array('success'=> $renewalMessage['result'], 'renewalMessage'=>$renewalMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Places a hold on an item that is available within the ILS. The location where the user would like to pickup
	 * the title must be specified as well als the record the user would like a hold placed on.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>bibId    - The id of the record within the ILS.</li>
	 * <li>campus   – the location where the patron would like to pickup the title (optional). If not provided, the patron's home location will be used.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be placed, false if the username or password were incorrect or the hold could not be placed.</li>
	 * <li>holdMessage – a reason why the method failed if success is false, or information about hold queue position if successful.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=renewAll&username=23025003575917&password=7604&bibId=1004012&campus=pa
	 * </code>
	 *
	 * Sample Response (successful hold):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Placement of hold request successful. You are number 1 in the queue."
	 * }}
	 * </code>
	 *
	 * Sample Response (failed hold):
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "holdMessage":"Unable to place a hold request. You have already requested this."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function placeHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$bibId = $_REQUEST['bibId'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$holdMessage = $this->catalog->placeHold($bibId, $user->cat_username, '', 'request');
			return array('success'=> $holdMessage['result'], 'holdMessage'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Places a hold on an eContent Record.  If the record is available for immediate usage, it will be checked out to the user.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be placed, false if the username or password were incorrect or the hold could not be placed.</li>
	 * <li>holdMessage – a reason why the method failed if success is false, or information about hold queue position if successful.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=placeEContentHold&username=23025003575917&password=1234&recordId=530
	 * </code>
	 *
	 * Sample Response (checkout):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"The title was checked out to you successfully. You may read it from the My eContent page within your account."
	 * }}
	 * </code>
	 *
	 * Sample Response (checkout):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Your hold was successfully placed, you are number 1 in the queue."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function placeEContentHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$recordId = $_REQUEST['recordId'];
		//Trim off econtentRecord from the front of the id if provided
		if (preg_match('/econtentRecord\d+/i', $recordId)){
			$recordId = substr($recordId, 14);
		}
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$driver = new EContentDriver();
			$holdMessage = $driver->placeHold($recordId, $user);
			return array('success'=> $holdMessage['result'], 'holdMessage'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Checks out an eContent Record to a user.  The record must be available to the user for the checkout to succeed.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the item could be checked out, false if the username or password were incorrect or the record could not be checked out.</li>
	 * <li>message – a reason why the method failed if success is false, or information indicating success.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=checkoutEContentItem&username=23025003575917&password=1234&recordId=530
	 * </code>
	 *
	 * Sample Response (checkout):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"The title was checked out to you successfully.  You may read it from the My eContent page within your account."
	 * }}
	 * </code>
	 *
	 * Sample Response (failed):
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "holdMessage":"There are no available copies of this title, please place a hold instead."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function checkoutEContentItem(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$recordId = $_REQUEST['recordId'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$driver = new EContentDriver();
			$response = $driver->checkoutRecord($recordId, $user);
			return array('success'=> $response['result'], 'message'=>$response['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Downloads an eContent file to the user's hard drive for offline usage.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>itemId   - The id of the item attached to the record that should be downloaded.</li>
	 * </ul>
	 *
	 * Returns:
	 * false if the username or password were incorrect or the item cannot be downloaded
	 * or the contents of the file that can be streamed directly to the client.
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function downloadEContentFile(){
		global $configArray;
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$recordId = $_REQUEST['recordId'];
		//Trim off econtentRecord from the front of the id if provided
		if (preg_match('/econtentRecord\d+/i', $recordId)){
			$recordId = substr($recordId, 14);
		}
		$itemId = $_REQUEST['itemId'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$driver = new EContentDriver();
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $recordId;
			if (!$eContentRecord->find(true)){
				return array('success'=>false, 'message'=>'Could not find the record in the database.');
			}
			//Check to see if the user has access to the title.
			if (!$driver->isRecordCheckedOutToUser($recordId)){
				return array('success'=>false, 'message'=>'The record is not checked out to you.');
			}
				
			$eContentItem = new EContentItem();
			$eContentItem->recordId = $recordId;
			$eContentItem->id = $itemId;
			if (!$eContentItem->find(true)){
				return array('success'=>false, 'message'=>'Could not find the item in the database.');
			}
			$driver->recordEContentAction($recordId, 'Download', $eContentRecord->accessType);
			$libraryPath = $configArray['EContent']['library'];
			if (isset($eContentItem->filename) && strlen($eContentItem->filename) > 0){
				$bookFile = "{$libraryPath}/{$eContentItem->filename}";
			}else{
				$bookFile = "{$libraryPath}/{$eContentItem->folder}";
			}
			if (strcasecmp($eContentItem->item_type, 'epub') == 0){
				require_once('sys/eReader/ebook.php');
				$ebook = new ebook($bookFile);

				//Return the contents of the epub file
				header("Content-Type: application/epub+zip;\n");
				header('Content-Length: ' . filesize($bookFile));
				header('Content-Description: ' . $ebook->getTitle());
				header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
				echo readfile($bookFile);
				exit();
			}else if (strcasecmp($eContentItem->item_type, 'pdf') == 0){
				header("Content-Type: application/pdf;\n");
				header('Content-Length: ' . filesize($bookFile));
				header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
				echo readfile($bookFile);
				exit();
			}else if (strcasecmp($eContentItem->item_type, 'kindle') == 0){
				header('Content-Length: ' . filesize($bookFile));
				header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
				echo readfile($bookFile);
				exit();
			}else if (strcasecmp($eContentItem->item_type, 'plucker') == 0){
				header('Content-Length: ' . filesize($bookFile));
				header('Content-Disposition: attachment; filename="' . basename($bookFile) . '"');
				echo readfile($bookFile);
				exit();
			}
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Place a hold within OverDrive.
	 * You should specify either the recordId of the title within VuFind or the overdrive id.
	 * The format is also required however when the user checks out the title they can override the format to checkout the version they want.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * <li>format - The format of the item to place a hold on within OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be placed, false if the username or password were incorrect or the hold could not be placed.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=placeOverDriveHold&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F&format=420
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your hold was placed successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function placeOverDriveHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (isset($_REQUEST['recordId'])){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$sourceUrl = $eContentRecord->sourceUrl;
				$overDriveId = substr($sourceUrl, -36);
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}
		$format = $_REQUEST['format'];

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$holdMessage = $driver->placeOverDriveHold($overDriveId, $format, $user);
			return array('success'=> $holdMessage['result'], 'message'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Cancel a hold within OverDrive
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * <li>format - The format of the record that was used when placing the hold.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be cancelled, false if the username or password were incorrect or the hold could not be cancelled.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=cancelOverDriveHold&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F&format=420
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your hold was cancelled successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function cancelOverDriveHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (isset($_REQUEST['recordId'])){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$sourceUrl = $eContentRecord->sourceUrl;
				$overDriveId = substr($sourceUrl, -36);
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}
		$format = $_REQUEST['format'];

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$result = $driver->cancelOverDriveHold($overDriveId, $format, $user);
			return array('success'=> $result['result'], 'message'=>$result['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Remove an item from the OverDrive WishList
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the title could be removed from the wishlist, false if the username or password were incorrect or the hold could not be removed from the wishlist.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=removeOverDriveItemFromWishlist&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"The title was successfully removed from your wishlist."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function removeOverDriveItemFromWishlist(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (isset($_REQUEST['recordId'])){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$sourceUrl = $eContentRecord->sourceUrl;
				$overDriveId = substr($sourceUrl, -36);
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$result = $driver->removeOverDriveItemFromWishlist($overDriveId, $user);
			return array('success'=> $result['result'], 'message'=>$result['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Add an item to the cart in OverDrive.
	 * In general, this method should not be used.  Instead you should call checkoutOverDriveItem which will first
	 * add the title to the cart and then process the cart.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * <li>format - The format of the item to place a hold on within OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the title could be removed from the wishlist, false if the username or password were incorrect or the hold could not be removed from the wishlist.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=addItemToOverDriveCart&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F&format=420
	 * </code>
	 *
	 * Sample Response (fail):
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "message":"There are no copies available for checkout. You can place a hold on the item instead."
	 * }}
	 * </code>
	 *
	 * Sample Response (pass):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"The title was added to your cart successfully. You have 30 minutes to check out the title before it is returned to the library's collection."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function addItemToOverDriveCart(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (isset($_REQUEST['recordId'])){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$sourceUrl = $eContentRecord->sourceUrl;
				$overDriveId = substr($sourceUrl, -36);
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}
		$format = $_REQUEST['format'];

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$holdMessage = $driver->addItemToOverDriveCart($overDriveId, $format, $user);
			return array('success'=> $holdMessage['result'], 'message'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Add an item to the wishlist in OverDrive
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the title could be removed from the wishlist, false if the username or password were incorrect or the hold could not be removed from the wishlist.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=addItemToOverDriveWishList&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"The title was added to your wishlist."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function addItemToOverDriveWishList(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (isset($_REQUEST['recordId'])){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$sourceUrl = $eContentRecord->sourceUrl;
				$overDriveId = substr($sourceUrl, -36);
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$message = $driver->addItemToOverDriveWishList($overDriveId, $user);
			return array('success'=> $message['result'], 'message'=>$message['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Checkout an item in OverDrive by first adding to the cart and then processing the cart.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId - The id of the record within the eContent database.</li>
	 * <li>or overdriveId - The id of the record in OverDrive.</li>
	 * <li>format - The format of the item to place a hold on within OverDrive.</li>
	 * <li>lendingPeriod - The number of days to checkout the title. (optional) </li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the account is valid and the title could be checked out, false if the username or password were incorrect or the hold could not be checked out.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=checkoutOverDriveItem&username=23025003575917&password=1234&overDriveId=A3365DAC-EEC3-4261-99D3-E39B7C94A90F&format=420
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your titles were checked out successfully. You may now download the titles from your Account."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function checkoutOverDriveItem(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (isset($_REQUEST['recordId'])){
			require_once('sys/eContent/EContentRecord.php');
			$eContentRecord = new EContentRecord();
			$eContentRecord->id = $_REQUEST['recordId'];
			if ($eContentRecord->find(true)){
				$overDriveId = $eContentRecord->getOverDriveId();
			}
		}else{
			$overDriveId = $_REQUEST['overDriveId'];
		}
		$format = $_REQUEST['format'];

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$lendingPeriod = isset($_REQUEST['lendingPeriod']) ? $_REQUEST['lendingPeriod'] : -1;
			$holdMessage = $driver->checkoutOverDriveItem($overDriveId, $format, $lendingPeriod, $user);
			return array('success'=> $holdMessage['result'], 'message'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}
	/**
	 * Process the account to checkout any titles within the OverDrive cart
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>lendingPeriod - The number of days to checkout the title. (optional) </li>
	 * </ul>
	 *
	 * Returns JSON encoded data as follows:
	 * <ul>
	 * <li>success – true if the cart was processed, false if the username or password were incorrect or the hold could not be removed from the wishlist.</li>
	 * <li>message – information about the process for display to the user.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=processOverDriveCart&username=23025003575917&password=1234&lendingPeriod=14
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"Your titles were checked out successfully. You may now download the titles from your Account."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 *
	 */
	function processOverDriveCart(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];

		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/OverDriveDriver.php');
			$driver = new OverDriveDriver();
			$lendingPeriod = isset($_REQUEST['lendingPeriod']) ? $_REQUEST['lendingPeriod'] : -1;
			$processCartResult = $driver->processOverDriveCart($user, $lendingPeriod);
			return array('success'=> $processCartResult['result'], 'message'=>$processCartResult['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Cancel a hold that was placed within the ILS.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>availableholdselected[] – an array of holds that should be canceled.  Each item should be specfied as <bibId>:<itemId>. BibId and itemId can be retrieved as part of the getPatronHolds API</li>
	 * <li>waitingholdselected[] - an array of holds that are not ready for pickup that should be canceled.  Each item should be specified as <bibId>:0.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be canceled, false if the username or password were incorrect or the hold could not be canceled.</li>
	 * <li>holdMessage – a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=cancelHold&username=23025003575917&password=1234&waitingholdselected[]=1003198
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * </code>
	 *
	 * Sample Response (failed):
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "holdMessage":"Your hold could not be cancelled. Please try again later or see your librarian."
	 * }}
	 * </code>
	 *
	 * Sample Response (succeeded):
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Your hold was cancelled successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function cancelHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$holdMessage = $this->catalog->updateHoldDetailed('', $user->cat_username, 'cancel');
			return array('success'=> $holdMessage['result'], 'holdMessage'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Cancel a hold on an eContent record.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>recordId – The id of the record that should have it's hold cancelled.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be canceled, false if the username or password were incorrect or the hold could not be canceled.</li>
	 * <li>holdMessage – a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=cancelEContentHold&username=23025003575917&password=1234&recordId=521
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Your hold was cancelled successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function cancelEContentHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$recordId = $_REQUEST['recordId'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$driver = new EContentDriver();
			$holdMessage = $driver->cancelHold($recordId);
			return array('success'=> $holdMessage['result'], 'holdMessage'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Freezes a hold that has been placed on a title within the ILS.  Only unavailable holds can be frozen.
	 * Note:  Horizon implements suspending and activating holds as a toggle.  If a hold is suspended, it will be activated
	 * and if a hold is active it will be suspended.  Care should be taken when calling the method with holds that are in the wrong state.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>waitingholdselected[] - an array of holds that are not ready for pickup that should be frozen. Each item should be specified as <bibId>:0.</li>
	 * <li>suspendDate - The date that the hold should be automatically reactivated.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be frozen, false if the username or password were incorrect or the hold could not be frozen.</li>
	 * <li>holdMessage – a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=freezeHold&username=23025003575917&password=1234&waitingholdselected[]=1004012:0&suspendDate=1/25/2012
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Your hold was updated successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function freezeHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$holdMessage = $this->catalog->updateHoldDetailed('', $user->cat_username, 'update', '', null, null, 'on');
			return array('success'=> $holdMessage['result'], 'holdMessage'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Freezes an eContent hold.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>ids[] - an array of ids that should be frozen.</li>
	 * <li>suspendDate - The date that the hold should be automatically reactivated.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be frozen, false if the username or password were incorrect or the hold could not be frozen.</li>
	 * <li>freezeResults – a list of results for each id that was frozen.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=freezeEContentHold&username=23025003575917&password=1234&ids[]=532&suspendDate=1/25/2012
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":false,
	 *   "freezeResults":{
	 *     "531":{
	 *       "success":false,
	 *       "title":"Toothful tales how we survived the sweet attack \/",
	 *       "error":"Could not find an active hold to suspend."
	 *     },
	 *     "521":{
	 *       "success":true,
	 *       "title":"Turn eye appeal into buy appeal how to easily transform your marketing pieces into dazzling, persuasive sales tools! \/",
	 *       "error":"The hold was suspended."
	 *     }
	 *   }
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function freezeEContentHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$eContentDriver = new EContentDriver();
			$ids = $_REQUEST['ids'];
			$suspendDate = strtotime($_REQUEST['suspendDate']);
			$suspendResults = $eContentDriver->suspendHolds($ids, $suspendDate);
			$success = true;
			foreach ($suspendResults as $suspendResult){
				if ($suspendResult['success'] == false){
					$success = false;
				}
			}
			return array('success'=> $success, 'freezeResults'=>$suspendResults);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Activates a hold that was previously suspended within the ILS.  Only unavailable holds can be activated.
	 * Note:  Horizon implements suspending and activating holds as a toggle.  If a hold is suspended, it will be activated
	 * and if a hold is active it will be suspended.  Care should be taken when calling the method with holds that are in the wrong state.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>waitingholdselected[] - an array of holds that are not ready for pickup that should be frozen. Each item should be specified as <bibId>:0.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be activated, false if the username or password were incorrect or the hold could not be activated.</li>
	 * <li>holdMessage – a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=activateHold&username=23025003575917&password=1234&waitingholdselected[]=1004012:0
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "holdMessage":"Your hold was updated successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function activateHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$holdMessage = $this->catalog->updateHoldDetailed('', $user->cat_username, 'update', '', null, null, 'off');
			return array('success'=> $holdMessage['result'], 'holdMessage'=>$holdMessage['message']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Activates a frozen eContent hold.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>id - Theid of the eContent record to activate the hold for.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be frozen, false if the username or password were incorrect or the hold could not be frozen.</li>
	 * <li>title – The title of the record.</li>
	 * <li>message – More information about the reactivation process.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=activateEContentHold&username=23025003575917&password=1234&ids=532
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "title":"Toothful tales how we survived the sweet attack \/",
	 *   "result":true,
	 *   "message":"Your hold was activated successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function activateEContentHold(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$eContentDriver = new EContentDriver();
			$id = $_REQUEST['id'];
			$reactivateResult = $eContentDriver->reactivateHold($id);
			return $reactivateResult;
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Returns an eContent record
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>id - The id of the record to return.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be frozen, false if the username or password were incorrect or the hold could not be frozen.</li>
	 * <li>freezeResults – a list of results for each id that was frozen.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=returnEContentRecord&username=23025003575917&password=1234&id=531
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "message":"The title was returned successfully."
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function returnEContentRecord(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			require_once('Drivers/EContentDriver.php');
			$eContentDriver = new EContentDriver();
			$id = $_REQUEST['id'];
			$returnResults = $eContentDriver->returnRecord($id);
			return $returnResults;
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Loads the reading history for the user.  Includes print, eContent, and OverDrive titles.
	 * Note: The return of this method can be quite lengthy if the patron has a large number of items in their reading history.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the hold could be canceled, false if the username or password were incorrect or the hold could not be canceled.</li>
	 * <li>holdMessage – a reason why the method failed if success is false</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=getPatronReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{
	 *   "success":true,
	 *   "readingHistory":[
	 *     {"recordId":"597608",
	 *      "checkout":"2011-03-18",
	 *      "checkoutTime":1300428000,
	 *      "lastCheckout":"2011-03-22",
	 *      "lastCheckoutTime":1300773600,
	 *      "title":"The wanderer",
	 *      "title_sort":"wanderer",
	 *      "author":"O.A.R. (Musical group)",
	 *      "format":"Music CD",
	 *      "format_category":"Music",
	 *      "isbn":"",
	 *      "upc":"803494030726"
	 *     },
	 *     {"recordId":"808990",
	 *      "checkout":"2011-03-18",
	 *      "checkoutTime":1300428000,
	 *      "lastCheckout":"2011-03-22",
	 *      "lastCheckoutTime":1300773600,
	 *      "title":"Seals \/",
	 *      "title_sort":"seals \/",
	 *      "author":"Sexton, Colleen A.,",
	 *      "format":"Book",
	 *      "format_category":"Books",
	 *      "isbn":"9781600140563",
	 *      "upc":""
	 *     }
	 *   ]
	 * }}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function getPatronReadingHistory(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$readingHistory = $this->catalog->getReadingHistory($user);

			return array('success'=>true, 'readingHistory'=>$readingHistory['titles']);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Allows reading history to be collected for the patron.  If this option is not selected,
	 * no reading history for the patron wil be stored.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the reading history could be turned on, false if the username or password were incorrect or the reading history could not be turned on.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=optIntoReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function optIntoReadingHistory(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$readingHistory = $this->catalog->doReadingHistoryAction($user, 'optIn', array());

			return array('success'=>true);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Stops collecting reading history for the patron and removes any reading history entries that have been collected already.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the reading history could be turned off, false if the username or password were incorrect or the reading history could not be turned off.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=optOutOfReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function optOutOfReadingHistory(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$readingHistory = $this->catalog->doReadingHistoryAction($user, 'optOut', array());

			return array('success'=>true);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Clears the user's reading history, but does not stop the collection of new data.  If items are currently checked out
	 * to the user they will be added to the reading history the next time cron runs.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the reading history could cleared, false if the username or password were incorrect or the reading history could not be cleared.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=deleteAllFromReadingHistory&username=23025003575917&password=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function deleteAllFromReadingHistory(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$readingHistory = $this->catalog->doReadingHistoryAction($user, 'deleteAll', array());

			return array('success'=>true);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

	/**
	 * Removes one or more titles from the user's reading history.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>selected - A list of record ids to be deleted from the reading history.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success – true if the account is valid and the items could be removed from the reading history, false if the username or password were incorrect or the items could not be removed from the reading history.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * http://catalog.douglascountylibraries.org/API/UserAPI?method=deleteSelectedFromReadingHistory&username=23025003575917&password=1234&selected[]=25855
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 *
	 * @author Mark Noble <mnoble@turningleaftech.com>
	 */
	function deleteSelectedFromReadingHistory(){
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$selectedTitles = $_REQUEST['selected'];
		global $user;
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !PEAR::isError($user)){
			$readingHistory = $this->catalog->doReadingHistoryAction($user, 'deleteMarked', $selectedTitles);

			return array('success'=>true);
		}else{
			return array('success'=>false, 'message'=>'Login unsuccessful');
		}
	}

}