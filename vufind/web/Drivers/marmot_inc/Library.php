<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/Holiday.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/NearbyBookStore.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LibraryFacetSetting.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LibrarySearchSource.php';

class Library extends DB_DataObject
{
	public $__table = 'library';    // table name
	public $libraryId; 				//int(11)
	public $subdomain; 				//varchar(15)
	public $displayName; 			//varchar(50)
	public $systemMessage;
	public $ilsCode;
	public $themeName; 				//varchar(15)
	public $searchesFile;        //varchar(15)
	public $restrictSearchByLibrary;
	public $includeDigitalCollection;
	public $includeOutOfSystemExternalLinks;
	public $allowProfileUpdates;   //tinyint(4)
	public $allowFreezeHolds;   //tinyint(4)
	public $scope; 					//smallint(6)
	public $useScope;		 		//tinyint(4)
	public $hideCommentsWithBadWords; //tinyint(4)
	public $showAmazonReviews;
	public $linkToAmazon;
	public $showStandardReviews;
	public $showHoldButton;
	public $showHoldButtonInSearchResults;
	public $showLoginButton;
	public $showTextThis;
	public $showEmailThis;
	public $showComments;
	public $showTagging;
	public $showRatings;
	public $illLink;
	public $askALibrarianLink;
	public $showCopiesLineInHoldingsSummary;
	public $showFavorites;
	public $showOtherEditionsPopup;
	public $showTableOfContentsTab;
	public $notesTabName;
	public $inSystemPickupsOnly;
	public $validPickupSystems;
	public $defaultPType;
	public $suggestAPurchase;
	public $boopsieLink;
	public $facetLabel;
	public $showAvailableAtAnyLocation;
	public $showEcommerceLink;
	public $payFinesLink;
	public $payFinesLinkText;
	public $minimumFineAmount;
	public $tabbedDetails;
	public $goldRushCode;
	public $repeatSearchOption;
	public $repeatInProspector;
	public $repeatInWorldCat;
	public $repeatInAmazon;
	public $repeatInOverdrive;
	public $overdriveAdvantageName;
	public $overdriveAdvantageProductsKey;
	public $systemsToRepeatIn;
	public $showMarmotResultsAtEndOfSearch;
	public $homeLink;
	public $homeLinkText;
	public $useHomeLinkInBreadcrumbs;
	public $showAdvancedSearchbox;
	public $enableBookCart;
	public $enablePospectorIntegration;
	public $showProspectorResultsAtEndOfSearch;
	public $prospectorCode;
	public $enableGenealogy;
	public $showHoldCancelDate;
	public $enableCourseReserves;
	public $enableSelfRegistration;
	public $showSeriesAsTab;
	public $showItsHere;
	public $holdDisclaimer;
	public $enableAlphaBrowse;
	public $enableMaterialsRequest;
	public $eContentLinkRules;
	public $includeNovelistEnrichment;
	public $applyNumberOfHoldingsBoost;
	public $show856LinksAsTab;
	public $showProspectorTitlesAsTab;
	public $worldCatUrl;
	public $worldCatQt;
	public $preferSyndeticsSummary;
	public $showSimilarAuthors;
	public $showSimilarTitles;
	public $showGoDeeper;
	public $defaultNotNeededAfterDays;
	public $homePageWidgetId;
	public $showCheckInGrid;
	public $boostByLibrary;
	public $recordsToBlackList;
	public $showWikipediaContent;
	public $eContentSupportAddress;
	public $restrictOwningBranchesAndSystems;
	public $allowPatronAddressUpdates;
	public $showWorkPhoneInProfile;
	public $showNoticeTypeInProfile;
	public $showPickupLocationInProfile;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Library',$k,$v); }

	function keys() {
		return array('libraryId', 'subdomain');
	}

	function getObjectStructure(){
		// get the structure for the library system's holidays
		$holidaysStructure = Holiday::getObjectStructure();

		// we don't want to make the libraryId property editable
		// because it is associated with this library system only
		unset($holidaysStructure['libraryId']);

		$nearbyBookStoreStructure = NearbyBookStore::getObjectStructure();
		unset($nearbyBookStoreStructure['weight']);
		unset($nearbyBookStoreStructure['libraryId']);

		$facetSettingStructure = LibraryFacetSetting::getObjectStructure();
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['libraryId']);
		unset($facetSettingStructure['numEntriesToShowByDefault']);
		unset($facetSettingStructure['showAsDropDown']);
		//unset($facetSettingStructure['sortMode']);

		$searchSourceStructure = LibrarySearchSource::getObjectStructure();
		unset($searchSourceStructure['weight']);
		unset($searchSourceStructure['libraryId']);

		$structure = array(
			'libraryId' => array('property'=>'libraryId', 'type'=>'label', 'label'=>'Library Id', 'description'=>'The unique id of the libary within the database'),
			'subdomain' => array('property'=>'subdomain', 'type'=>'text', 'label'=>'Subdomain', 'description'=>'A unique id to identify the library within the system'),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'A name to identify the library within the system', 'size'=>'40'),
			'systemMessage' => array('property'=>'systemMessage', 'type'=>'html', 'label'=>'System Message', 'description'=>'A message to be displayed at the top of the screen', 'size'=>'80', 'maxLength' =>'512', 'allowableTags' => '<a><b><em>', 'hideInLists' => true),
			array('property'=>'displaySection', 'type' => 'section', 'label' =>'Basic Display', 'hideInLists' => true, 'properties' => array(
				'themeName' => array('property'=>'themeName', 'type'=>'text', 'label'=>'Theme Name', 'description'=>'The name of the theme which should be used for the library', 'hideInLists' => true, 'default' => 'default'),
				'homeLink' => array('property'=>'homeLink', 'type'=>'text', 'label'=>'Home Link', 'description'=>'The location to send the user when they click on the home button or logo.  Use default or blank to go back to the vufind home location.', 'size'=>'40', 'hideInLists' => true,),
				'useHomeLinkInBreadcrumbs' => array('property'=>'useHomeLinkInBreadcrumbs', 'type'=>'checkbox', 'label'=>'Use Home Link in Breadcrumbs', 'description'=>'Whether or not the home link should be used in the breadcumbs.', 'hideInLists' => true,),
				'homeLinkText' => array('property'=>'homeLinkText', 'type'=>'text', 'label'=>'Home Link Text', 'description'=>'The text to show for the Home breadcrumb link', 'size'=>'40', 'hideInLists' => true, 'default' => 'Home'),
				'homePageWidgetId' => array('property'=>'homePageWidgetId', 'type'=>'integer', 'label'=>'Home Page Widget Id', 'description'=>'An id for the list widget to display on the home page', 'hideInLists' => true, 'default' => 0),
				'illLink'  => array('property'=>'illLink', 'type'=>'url', 'label'=>'ILL Link', 'description'=>'A link to a library system specific ILL page', 'size'=>'80', 'hideInLists' => true,),
				'askALibrarianLink'  => array('property'=>'askALibrarianLink', 'type'=>'url', 'label'=>'Ask a Librarian Link', 'description'=>'A link to a library system specific Ask a Librarian page', 'size'=>'80', 'hideInLists' => true,),
				'suggestAPurchase'  => array('property'=>'suggestAPurchase', 'type'=>'url', 'label'=>'Suggest a Purchase Link', 'description'=>'A link to a library system specific Suggest a Purchase page', 'size'=>'80', 'hideInLists' => true,),
				'eContentSupportAddress'  => array('property'=>'eContentSupportAddress', 'type'=>'email', 'label'=>'E-Content Support Address', 'description'=>'An e-mail address to receive support requests for patrons with eContent problems.', 'size'=>'80', 'hideInLists' => true, 'default'=>'askmarmot@marmot.org'),
				'enableBookCart'  => array('property'=>'enableBookCart', 'type'=>'checkbox', 'label'=>'Enable Book Cart', 'description'=>'Whether or not the Book Cart should be used for this library.', 'hideInLists' => true, 'default' => 1),
				'enableGenealogy' => array('property'=>'enableGenealogy', 'type'=>'checkbox', 'label'=>'Enable Genealogy Functionality', 'description'=>'Whether or not patrons can search genealogy.', 'hideInLists' => true, 'default' => 1),
				'enableCourseReserves' => array('property'=>'enableCourseReserves', 'type'=>'checkbox', 'label'=>'Enable Repeat Search in Course Reserves', 'description'=>'Whether or not patrons can repeat searches within course reserves.', 'hideInLists' => true,),
				'enableAlphaBrowse' => array('property'=>'enableAlphaBrowse', 'type'=>'checkbox', 'label'=>'Enable Alphabetic Browse', 'description'=>'Enable Alphabetic Browsing of titles, authors, etc.', 'hideInLists' => true, 'default' => 1),
				'enableMaterialsRequest' => array('property'=>'enableMaterialsRequest', 'type'=>'checkbox', 'label'=>'Enable Materials Request', 'description'=>'Enable Materials Request functionality so patrons can request items not in the catalog.', 'hideInLists' => true,),
				'boopsieLink'  => array('property'=>'boopsieLink', 'type'=>'url', 'label'=>'Boopsie Link', 'description'=>'A link to the Boopsie Mobile App', 'size'=>'80', 'hideInLists' => true,),
				'eContentLinkRules' => array('property'=>'eContentLinkRules', 'type'=>'text', 'label'=>'EContent Link Rules', 'description'=>'A regular expression defining a set of criteria to determine whether or not a link belongs to this library.', 'size'=>'40'),
			)),

			array('property'=>'ilsSection', 'type' => 'section', 'label' =>'ILS/Account Integration', 'hideInLists' => true, 'properties' => array(
				'ilsCode' => array('property'=>'ilsCode', 'type'=>'text', 'label'=>'ILS Code', 'description'=>'The location code that all items for this location start with.', 'size'=>'4', 'hideInLists' => false,),
				'scope'  => array('property'=>'scope', 'type'=>'text', 'label'=>'Scope', 'description'=>'The scope for the system in Millennium to refine holdings for the user.', 'size'=>'4', 'hideInLists' => true,),
				'useScope'  => array('property'=>'useScope', 'type'=>'checkbox', 'label'=>'Use Scope', 'description'=>'Whether or not the scope should be used when displaying holdings.', 'hideInLists' => true,),
				'defaultPType'  => array('property'=>'defaultPType', 'type'=>'text', 'label'=>'Default P-Type', 'description'=>'The P-Type to use when accessing a subdomain if the patron is not logged in.'),
				'showLoginButton'  => array('property'=>'showLoginButton', 'type'=>'checkbox', 'label'=>'Show Login Button', 'description'=>'Whether or not the login button is displayed so patrons can login to the site', 'hideInLists' => true, 'default' => 1),
				'enableSelfRegistration' => array('property'=>'enableSelfRegistration', 'type'=>'checkbox', 'label'=>'Enable Self Registration', 'description'=>'Whether or not patrons can self register on the site', 'hideInLists' => true,),
				'allowProfileUpdates'  => array('property'=>'allowProfileUpdates', 'type'=>'checkbox', 'label'=>'Allow Profile Updates', 'description'=>'Whether or not the user can update their own profile.', 'hideInLists' => true, 'default' => 1),
				'showHoldButton'  => array('property'=>'showHoldButton', 'type'=>'checkbox', 'label'=>'Show Hold Button', 'description'=>'Whether or not the hold button is displayed so patrons can place holds on items', 'hideInLists' => true, 'default' => 1),
				'showHoldButtonInSearchResults'  => array('property'=>'showHoldButtonInSearchResults', 'type'=>'checkbox', 'label'=>'Show Hold Button within the search results', 'description'=>'Whether or not the hold button is displayed within the search results so patrons can place holds on items', 'hideInLists' => true, 'default' => 1),
				'holdDisclaimer' => array('property'=>'holdDisclaimer', 'type'=>'textarea', 'label'=>'Hold Disclaimer', 'description'=>'A disclaimer to display to patrons when they are placing a hold on items letting them know that their information may be available to other libraries.  Leave blank to not show a discalaimer.', 'hideInLists' => true,),
				'showHoldCancelDate'   => array('property'=>'showHoldCancelDate', 'type'=>'checkbox', 'label'=>'Show Cancellation Date', 'description'=>'Whether or not the patron should be able to set a cancellation date (not needed after date) when placing holds.', 'hideInLists' => true, 'default' => 1),
				'defaultNotNeededAfterDays'=> array('property'=>'defaultNotNeededAfterDays', 'type'=>'integer', 'label'=>'Default Not Needed After Days', 'description'=>'Number of days to use for not needed after date by default. Use -1 for no default.', 'hideInLists' => true,),
				'inSystemPickupsOnly'  => array('property'=>'inSystemPickupsOnly', 'type'=>'checkbox', 'label'=>'In System Pickups Only', 'description'=>'Restrict pickup locations to only locations within the library system which is active.', 'hideInLists' => true,),
				'validPickupSystems'  => array('property'=>'validPickupSystems', 'type'=>'text', 'label'=>'Valid Pickup Systems', 'description'=>'A list of library codes that can be used as pickup locations separated by pipes |', 'size'=>'20', 'hideInLists' => true,),
				'allowFreezeHolds'  => array('property'=>'allowFreezeHolds', 'type'=>'checkbox', 'label'=>'Allow Freezing Holds', 'description'=>'Whether or not the user can freeze their holds.', 'hideInLists' => true, 'default' => 1),
				'allowPatronAddressUpdates' => array('property' => 'allowPatronAddressUpdates', 'type'=>'checkbox', 'label'=>'Allow Patrons to Update Their Address', 'description'=>'Whether or not patrons should be able to update their own address in their profile.', 'hideInLists' => true, 'default' => 1),
				'showWorkPhoneInProfile' => array('property' => 'showWorkPhoneInProfile', 'type'=>'checkbox', 'label'=>'Show Work Phone in Profile', 'description'=>'Whether or not patrons should be able to change a secondary/work phone number in their profile.', 'hideInLists' => true, 'default' => 0),
				'showNoticeTypeInProfile' => array('property' => 'showNoticeTypeInProfile', 'type'=>'checkbox', 'label'=>'Show Notice Type in Profile', 'description'=>'Whether or not patrons should be able to change how they receive notices in their profile.', 'hideInLists' => true, 'default' => 0),
				'showPickupLocationInProfile' => array('property' => 'showPickupLocationInProfile', 'type'=>'checkbox', 'label'=>'Allow Patrons to Update Their Pickup Location', 'description'=>'Whether or not patrons should be able to update their preferred pickup location in their profile.', 'hideInLists' => true, 'default' => 0),

			)),
			array('property'=>'ecommerceSection', 'type' => 'section', 'label' =>'Fines/e-commerce', 'hideInLists' => true, 'properties' => array(
				'showEcommerceLink' => array('property'=>'showEcommerceLink', 'type'=>'checkbox', 'label'=>'Show E-Commerce Link', 'description'=>'Whether or not users should be given a link to classic opac to pay fines', 'hideInLists' => true,),
				'payFinesLink' => array('property'=>'payFinesLink', 'type'=>'text', 'label'=>'Pay Fines Link', 'description'=>'The link to pay fines.  Leave as default to link to classic (should have eCommerce link enabled)', 'hideInLists' => true, 'default' => 'default', 'size' => 80),
				'payFinesLinkText' => array('property'=>'payFinesLinkText', 'type'=>'text', 'label'=>'Pay Fines Link Text', 'description'=>'The text when linking to pay fines.', 'hideInLists' => true, 'default' => 'Click to Pay Fines Online ', 'size' => 80),
				'minimumFineAmount' => array('property'=>'minimumFineAmount', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Minimum Fine Amount', 'description'=>'The minimum fine amount to display the e-commerce link', 'hideInLists' => true,),
			)),
			array('property'=>'searchingSection', 'type' => 'section', 'label' =>'Searching', 'hideInLists' => true, 'properties' => array(
				'facetLabel' => array('property'=>'facetLabel', 'type'=>'text', 'label'=>'Facet Label', 'description'=>'The label for the library system in the Library System Facet.', 'size'=>'40', 'hideInLists' => true,),
				'restrictSearchByLibrary' => array('property'=>'restrictSearchByLibrary', 'type'=>'checkbox', 'label'=>'Restrict Search By Library', 'description'=>'Whether or not search results should only include titles from this library', 'hideInLists' => true),
				'includeDigitalCollection' => array('property'=>'includeDigitalCollection', 'type'=>'checkbox', 'label'=>'Include Digital Collection', 'description'=>'Whether or not titles from the digital collection should be included in searches', 'hideInLists' => true),
				'includeOutOfSystemExternalLinks' => array('property' => 'includeOutOfSystemExternalLinks', 'type'=>'checkbox', 'label'=>'Include Out Of System External Links', 'description'=>'Whether or not to include external links from other library systems.  Should only be enabled for Marmot global scope.', 'hideInLists' => true, 'default'=>0),
				'boostByLibrary' => array('property'=>'boostByLibrary', 'type'=>'checkbox', 'label'=>'Boost By Library', 'description'=>'Whether or not boosting of titles owned by this library should be applied', 'hideInLists' => true),
				'restrictOwningBranchesAndSystems' => array('property'=>'restrictOwningBranchesAndSystems', 'type'=>'checkbox', 'label'=>'Restrict Owning Branch and System Facets to this library', 'description'=>'Whether or not the Owning Branch and Owning System Facets will only display values relevant to this library.', 'hideInLists' => true),
				'showAvailableAtAnyLocation' => array('property'=>'showAvailableAtAnyLocation', 'type'=>'checkbox', 'label'=>'Show Available At Any Location?', 'description'=>'Whether or not to show any Marmot Location within the Available At facet', 'hideInLists' => true),
				'searchesFile' => array('property'=>'searchesFile', 'type'=>'text', 'label'=>'Searches File', 'description'=>'The name of the searches file which should be used while searching', 'hideInLists' => true,),
				'repeatSearchOption'  => array('property'=>'repeatSearchOption', 'type'=>'enum', 'values'=>array('none'=>'None', 'librarySystem'=>'Library System','marmot'=>'Marmot'), 'label'=>'Repeat Search Options', 'description'=>'Where to allow repeating search. Valid options are: none, librarySystem, marmot, all'),
				'systemsToRepeatIn'  => array('property'=>'systemsToRepeatIn', 'type'=>'text', 'label'=>'Systems To Repeat In', 'description'=>'A list of library codes that you would like to repeat search in separated by pipes |.', 'size'=>'20', 'hideInLists' => true,),
				'showMarmotResultsAtEndOfSearch' => array('property'=>'showMarmotResultsAtEndOfSearch', 'type'=>'checkbox', 'label'=>'Show Marmot Results in Scoped Search', 'description'=>'Whether or not the VuFind should show search results from Marmot at the end of scoped searches.', 'hideInLists' => true, 'default' => 1),
				'showAdvancedSearchbox'  => array('property'=>'showAdvancedSearchbox', 'type'=>'checkbox', 'label'=>'Show Advanced Search Link', 'description'=>'Whether or not users should see the advanced search link next to the search box.  It will still appear in the footer.', 'hideInLists' => true, 'default' => 1),
				'applyNumberOfHoldingsBoost' => array('property'=>'applyNumberOfHoldingsBoost', 'type'=>'checkbox', 'label'=>'Apply Number Of Holdings Boost', 'description'=>'Whether or not the relevance will use boosting by number of holdings in the catalog.', 'hideInLists' => true, 'default' => 1),
				'repeatInAmazon'  => array('property'=>'repeatInAmazon', 'type'=>'checkbox', 'label'=>'Repeat In Amazon', 'description'=>'Turn on to allow repeat search in Amazon functionality.', 'hideInLists' => true),
				'recordsToBlackList' => array('property'=>'recordsToBlackList', 'type'=>'textarea', 'label'=>'Records to deaccession', 'description'=>'A list of records to deaccession (hide) in search results.  Enter one record per line.', 'hideInLists' => true,),
			)),

			array('property'=>'enrichmentSection', 'type' => 'section', 'label' =>'Catalog Enrichment', 'hideInLists' => true, 'properties' => array(
				'showAmazonReviews'  => array('property'=>'showAmazonReviews', 'type'=>'checkbox', 'label'=>'Show Amazon Reviews', 'description'=>'Whether or not reviews from Amazon are displayed on the full record page.', 'hideInLists' => true,),
				'linkToAmazon'  => array('property'=>'linkToAmazon', 'type'=>'checkbox', 'label'=>'Link To Amazon', 'description'=>'Whether or not a purchase on Amazon link should be shown.  Should generally match showAmazonReviews setting', 'hideInLists' => true,),
				'showStandardReviews'  => array('property'=>'showStandardReviews', 'type'=>'checkbox', 'label'=>'Show Standard Reviews', 'description'=>'Whether or not reviews from Content Cafe/Syndetics are displayed on the full record page.', 'hideInLists' => true, 'default' => 1),
				'preferSyndeticsSummary' => array('property'=>'preferSyndeticsSummary', 'type'=>'checkbox', 'label'=>'Prefer Syndetics Summary', 'description'=>'Whether or not the Syndetics Summary should be preferred over the Summary in the Marc Record.', 'hideInLists' => true, 'default' => 1),
				'showSimilarAuthors' => array('property'=>'showSimilarAuthors', 'type'=>'checkbox', 'label'=>'Show Similar Authors', 'description'=>'Whether or not Similar Authors from Novelist is shown.', 'default' => 1, 'hideInLists' => true,),
				'showSimilarTitles' => array('property'=>'showSimilarTitles', 'type'=>'checkbox', 'label'=>'Show Similar Titles', 'description'=>'Whether or not Similar Titles from Novelist is shown.', 'default' => 1, 'hideInLists' => true,),
				'showGoDeeper' => array('property'=>'showGoDeeper', 'type'=>'checkbox', 'label'=>'Show Go Deeper', 'description'=>'Whether or not Go Deeper link is shown in full record page', 'default' => 1, 'hideInLists' => true,),
				'showRatings'  => array('property'=>'showRatings', 'type'=>'checkbox', 'label'=>'Show Ratings', 'description'=>'Whether or not ratings are shown', 'hideInLists' => true, 'default' => 1),
				'showFavorites'  => array('property'=>'showFavorites', 'type'=>'checkbox', 'label'=>'Show Favorites', 'description'=>'Whether or not users can maintain favorites lists', 'hideInLists' => true, 'default' => 1),
				'showOtherEditionsPopup' => array('property'=>'showOtherEditionsPopup', 'type'=>'checkbox', 'label'=>'Show Other Editions Popup', 'description'=>'Whether or not the Other Formats and Langauges popup will be shown (if not shows Other Editions sidebar)', 'default'=>'1', 'hideInLists' => true,),
				'showWikipediaContent' => array('property'=>'showWikipediaContent', 'type'=>'checkbox', 'label'=>'Show Wikipedia Content', 'description'=>'Whether or not Wikipedia content should be shown on author page', 'default'=>'1', 'hideInLists' => true,),
			)),
			array('property'=>'fullRecordSection', 'type' => 'section', 'label' =>'Full Record Display', 'hideInLists' => true, 'properties' => array(
				'tabbedDetails'  => array('property'=>'tabbedDetails', 'type'=>'checkbox', 'label'=>'Tabbed Details', 'description'=>'Whether or not details (reviews, copies, citations, etc) should be shown in tabs', 'hideInLists' => true, 'default' => 1),
				'showTextThis'  => array('property'=>'showTextThis', 'type'=>'checkbox', 'label'=>'Show Text This', 'description'=>'Whether or not the Text This link is shown', 'hideInLists' => true, 'default' => 1),
				'showEmailThis'  => array('property'=>'showEmailThis', 'type'=>'checkbox', 'label'=>'Show Email This', 'description'=>'Whether or not the Email This link is shown', 'hideInLists' => true, 'default' => 1),
				'showComments'  => array('property'=>'showComments', 'type'=>'checkbox', 'label'=>'Show Comments', 'description'=>'Whether or not comments are shown (also disables adding comments)', 'hideInLists' => true, 'default' => 1),
				'hideCommentsWithBadWords'  => array('property'=>'hideCommentsWithBadWords', 'type'=>'checkbox', 'label'=>'Hide Comments with Bad Words', 'description'=>'If checked, any comments with bad words are completely removed from the user interface for everyone except the original poster.', 'hideInLists' => true,),
				'showTagging'  => array('property'=>'showTagging', 'type'=>'checkbox', 'label'=>'Show Tagging', 'description'=>'Whether or not tags are shown (also disables adding tags)', 'hideInLists' => true, 'default' => 1),
				'showTableOfContentsTab' => array('property'=>'showTableOfContentsTab', 'type'=>'checkbox', 'label'=>'Show Table of Contents Tab', 'description'=>'Whether or not a separate tab will be shown for table of contents 505 field.', 'hideInLists' => true, 'default' => 1),
				'notesTabName' => array('property'=>'notesTabName', 'type'=>'text', 'label'=>'Notes Tab Name', 'description'=>'Text to display for the the notes tab.', 'size'=>'40', 'maxLength' => '50', 'hideInLists' => true, 'default' => 'Notes'),
				'exportOptions' => array('property'=>'exportOptions', 'type'=>'text', 'label'=>'Export Options', 'description'=>'A list of export options that should be enabled separated by pipes.  Valid values are currently RefWorks and EndNote.', 'size'=>'40', 'hideInLists' => true,),
				'showSeriesAsTab'  => array('property'=>'showSeriesAsTab', 'type'=>'checkbox', 'label'=>'Show Series as Tab', 'description'=>'Whether or not series information should be shown in a tab or in a scrollable window.', 'hideInLists' => true,),
				'show856LinksAsTab'  => array('property'=>'show856LinksAsTab', 'type'=>'checkbox', 'label'=>'Show 856 Links as Tab', 'description'=>'Whether or not 856 links will be shown in their own tab or on the same tab as holdings.', 'hideInLists' => true, 'default' => 1),
				'showProspectorTitlesAsTab' => array('property'=>'showProspectorTitlesAsTab', 'type'=>'checkbox', 'label'=>'Show Prospector Titles as Tab', 'description'=>'Whether or not Prospector TItles links will be shown in their own tab or in the sidebar in full record view.', 'default' => 1, 'hideInLists' => true,),
				'showCheckInGrid' => array('property'=>'showCheckInGrid', 'type'=>'checkbox', 'label'=>'Show Check-in Grid', 'description'=>'Whether or not the check-in grid is shown for periodicals.', 'default' => 1, 'hideInLists' => true,),
			)),
			array('property'=>'holdingsSummarySection', 'type' => 'section', 'label' =>'Holdings Summary', 'hideInLists' => true, 'properties' => array(
				'showCopiesLineInHoldingsSummary' => array('property'=>'showCopiesLineInHoldingsSummary', 'type'=>'checkbox', 'label'=>'Show Copies Line In Holdings Summary', 'description'=>'Whether or not the number of copies should be shown in the holdins summary', 'default'=>'1', 'hideInLists' => true,),
				'showItsHere' => array('property'=>'showItsHere', 'type'=>'checkbox', 'label'=>'Show It\'s Here', 'description'=>'Whether or not the holdings summray should show It\'s here based on IP and the currently logged in patron\'s location.', 'hideInLists' => true, 'default' => 1),
			)),
			array('property'=>'goldrushSection', 'type' => 'section', 'label' =>'Gold Rush', 'hideInLists' => true, 'properties' => array(
				'goldRushCode'  => array('property'=>'goldRushCode', 'type'=>'text', 'label'=>'Gold Rush Inst Code', 'description'=>'The INST Code to use with Gold Rush.  Leave blank to not link to Gold Rush.', 'hideInLists' => true,),
			)),

			array('property'=>'prospectorSection', 'type' => 'section', 'label' =>'Prospector', 'hideInLists' => true, 'properties' => array(
				'repeatInProspector'  => array('property'=>'repeatInProspector', 'type'=>'checkbox', 'label'=>'Repeat In Prospector', 'description'=>'Turn on to allow repeat search in Prospector functionality.', 'hideInLists' => true, 'default' => 1),
				'prospectorCode' => array('property'=>'prospectorCode', 'type'=>'text', 'label'=>'Prospector Code', 'description'=>'The code used to identify this location within Prospector. Leave blank if items for this location are not in Prospector.', 'hideInLists' => true,),
				'enablePospectorIntegration'=> array('property'=>'enablePospectorIntegration', 'type'=>'checkbox', 'label'=>'Enable Prospector Integration', 'description'=>'Whether or not Prospector Integrations should be displayed for this library.', 'hideInLists' => true, 'default' => 1),
				'showProspectorResultsAtEndOfSearch' => array('property'=>'showProspectorResultsAtEndOfSearch', 'type'=>'checkbox', 'label'=>'Show Prospector Results At End Of Search', 'description'=>'Whether or not Prospector Search Results should be shown at the end of search results.', 'hideInLists' => true, 'default' => 1),
			)),
			array('property'=>'worldCatSection', 'type' => 'section', 'label' =>'WorldCat', 'hideInLists' => true, 'properties' => array(
				'repeatInWorldCat'  => array('property'=>'repeatInWorldCat', 'type'=>'checkbox', 'label'=>'Repeat In WorldCat', 'description'=>'Turn on to allow repeat search in WorldCat functionality.', 'hideInLists' => true,),
				'worldCatUrl' => array('property'=>'worldCatUrl', 'type'=>'text', 'label'=>'WorldCat URL', 'description'=>'A custom World Cat URL to use while searching.', 'hideInLists' => true, 'size'=>'80'),
				'worldCatQt' => array('property'=>'worldCatQt', 'type'=>'text', 'label'=>'WorldCat QT', 'description'=>'A custom World Cat QT term to use while searching.', 'hideInLists' => true, 'size'=>'40'),
			)),

			array('property'=>'overdriveSection', 'type' => 'section', 'label' =>'OverDrive', 'hideInLists' => true, 'properties' => array(
				'repeatInOverdrive' => array('property'=>'repeatInOverdrive', 'type'=>'checkbox', 'label'=>'Repeat In Overdrive', 'description'=>'Turn on to allow repeat search in Overdrive functionality.', 'hideInLists' => true, 'default' => 0),
				'overdriveAdvantageName' => array('property'=>'overdriveAdvantageName', 'type'=>'text', 'label'=>'Overdrive Advantage Name', 'description'=>'The name of the OverDrive Advantage account if any.', 'size'=>'80', 'hideInLists' => true,),
				'overdriveAdvantageProductsKey' => array('property'=>'overdriveAdvantageProductsKey', 'type'=>'text', 'label'=>'Overdrive Advantage Products Key', 'description'=>'The products key for use when building urls to the API from the advantageAccounts call.', 'size'=>'80', 'hideInLists' => false,),
			)),

			'holidays' => array(
				'property' => 'holidays',
				'type'=> 'oneToMany',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'Holiday',
				'structure' => $holidaysStructure,
				'label' => 'Holidays',
				'description' => 'Holidays',
				//'hideInLists' => true,
				'sortable' => false,
				'storeDb' => true
			),
			'nearbyBookStores' => array(
				'property'=>'nearbyBookStores',
				'type'=>'oneToMany',
				'label'=>'Nearby Book Stores',
				'description'=>'A list of book stores to search',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'NearbyBookStore',
				'structure' => $nearbyBookStoreStructure,
				//'hideInLists' => true,
				'sortable' => true,
				'storeDb' => true
			),

			'facets' => array(
				'property'=>'facets',
				'type'=>'oneToMany',
				'label'=>'Facets',
				'description'=>'A list of facets to display in search results',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibraryFacetSetting',
				'structure' => $facetSettingStructure,
				//'hideInLists' => true,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),

			'searchSources' => array(
				'property'=>'searchSources',
				'type'=>'oneToMany',
				'label'=>'Search Sources',
				'description'=>'Searches to display to the user',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'LibrarySearchSource',
				'structure' => $searchSourceStructure,
				//'hideInLists' => true,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),
		);
		foreach ($structure as $fieldName => $field){
			if (isset($field['property'])){
				$field['propertyOld'] = $field['property'] . 'Old';
				$structure[$fieldName] = $field;
			}
		}
		return $structure;
	}

	static function getSearchLibrary($searchSource = null){
		if (is_null($searchSource)){
			$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			if (strpos($searchSource, 'library') === 0){
				$trimmedSearchSource = str_replace('library', '', $searchSource);
				require_once  ROOT_DIR . '/Drivers/marmot_inc/LibrarySearchSource.php';
				$librarySearchSource = new LibrarySearchSource();
				$librarySearchSource->id = $trimmedSearchSource;
				if ($librarySearchSource->find(true)){
					$searchSource = $librarySearchSource;
				}
			}
		}
		if (is_object($searchSource)){
			$scopingSetting = $searchSource->catalogScoping;
		}else{
			$scopingSetting = $searchSource;
		}
		if ($scopingSetting == 'local' || $scopingSetting == 'econtent' || $scopingSetting == 'library' || $scopingSetting == 'location'){
			return Library::getActiveLibrary();
		}else if ($scopingSetting == 'marmot' || $scopingSetting == 'unscoped'){
			return null;
		}else{
			$location = Location::getSearchLocation();
			if (is_null($location)){
				//Check to see if we have a library for the subdomain
				$library = new Library();
				$library->subdomain = $scopingSetting;
				$library->find();
				if ($library->N > 0){
					$library->fetch();
					return clone($library);
				}
				return null;
			}else{
				return self::getLibraryForLocation($location->locationId);
			}
		}
	}

	static function getActiveLibrary(){
		global $library;
		//First check to see if we have a library loaded based on subdomain (loaded in index)
		if (isset($library)) {
			return $library;
		}
		//If there is only one library, that library is active by default.
		$activeLibrary = new Library();
		$activeLibrary->find();
		if ($activeLibrary->N == 1){
			$activeLibrary->fetch();
			return $activeLibrary;
		}
		//Next check to see if we are in a library.
		/** @var Location $locationSingleton */
		global $locationSingleton;
		$physicalLocation = $locationSingleton->getActiveLocation();
		if (!is_null($physicalLocation)){
			//Load the library based on the home branch for the user
			return self::getLibraryForLocation($physicalLocation->libraryId);
		}
		return null;
	}

	static function getPatronHomeLibrary(){
		global $user;
		//Finally check to see if the user has logged in and if so, use that library
		if (isset($user) && $user != false){
			//Load the library based on the home branch for the user
			return self::getLibraryForLocation($user->homeLocationId);
		}else{
			return null;
		}

	}

	static function getLibraryForLocation($locationId){
		if (isset($locationId)){
			$libLookup = new Library();
			require_once(ROOT_DIR . '/Drivers/marmot_inc/Location.php');
			$libLookup->whereAdd('libraryId = (SELECT libraryId FROM location WHERE locationId = ' . $libLookup->escape($locationId) . ')');
			$libLookup->find();
			if ($libLookup->N > 0){
				$libLookup->fetch();
				return clone $libLookup;
			}
		}
		return null;
	}

	private $data = array();
	public function __get($name){
		if ($name == "holidays") {
			if (!isset($this->holidays) && $this->libraryId){
				$this->holidays = array();
				$holiday = new Holiday();
				$holiday->libraryId = $this->libraryId;
				$holiday->orderBy('date');
				$holiday->find();
				while($holiday->fetch()){
					$this->holidays[$holiday->id] = clone($holiday);
				}
			}
			return $this->holidays;
		}elseif ($name == "nearbyBookStores") {
			if (!isset($this->nearbyBookStores) && $this->libraryId){
				$this->nearbyBookStores = array();
				$store = new NearbyBookStore();
				$store->libraryId = $this->libraryId;
				$store->orderBy('weight');
				$store->find();
				while($store->fetch()){
					$this->nearbyBookStores[$store->id] = clone($store);
				}
			}
			return $this->nearbyBookStores;
		}elseif ($name == "facets") {
			if (!isset($this->facets) && $this->libraryId){
				$this->facets = array();
				$facet = new LibraryFacetSetting();
				$facet->libraryId = $this->libraryId;
				$facet->orderBy('weight');
				$facet->find();
				while($facet->fetch()){
					$this->facets[$facet->id] = clone($facet);
				}
			}
			return $this->facets;
		}elseif ($name == 'searchSources'){
			if (!isset($this->searchSources) && $this->libraryId){
				$this->searchSources = array();
				$searchSource = new LibrarySearchSource();
				$searchSource->libraryId = $this->libraryId;
				$searchSource->orderBy('weight');
				$searchSource->find();
				while($searchSource->fetch()){
					$this->searchSources[$searchSource->id] = clone($searchSource);
				}
			}
			return $this->searchSources;
		}else{
			return $this->data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "holidays") {
			$this->holidays = $value;
		}elseif ($name == "nearbyBookStores") {
			$this->nearbyBookStores = $value;
		}elseif ($name == "facets") {
			$this->facets = $value;
		}elseif ($name == 'searchSources'){
			$this->searchSources = $value;
		}else{
			$this->data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveHolidays();
			$this->saveNearbyBookStores();
			$this->saveFacets();
			$this->saveSearchSources();
			return $ret;
		}
	}

	/**
	 * Override the update functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveHolidays();
			$this->saveNearbyBookStores();
			$this->saveFacets();
			$this->saveSearchSources();
			return $ret;
		}
	}

	public function saveSearchSources(){
		if (isset ($this->searchSources) && is_array($this->searchSources)){
			/** @var SearchSource $searchSource */
			foreach ($this->searchSources as $searchSource){
				if (isset($searchSource->deleteOnSave) && $searchSource->deleteOnSave == true){
					$searchSource->delete();
				}else{
					if (isset($searchSource->id) && is_numeric($searchSource->id)){
						$ret = $searchSource->update();
					}else{
						$searchSource->libraryId = $this->libraryId;
						$searchSource->insert();
					}
				}
			}
			unset($this->facets);
		}
	}

	public function clearSearchSources(){
		$facets = new LibrarySearchSource();
		$facets->libraryId = $this->libraryId;
		$facets->delete();
		$this->searchSources = array();
	}

	public function saveFacets(){
		if (isset ($this->facets) && is_array($this->facets)){
			/** @var LibraryFacetSetting $facet */
			foreach ($this->facets as $facet){
				if (isset($facet->deleteOnSave) && $facet->deleteOnSave == true){
					$facet->delete();
				}else{
					if (isset($facet->id) && is_numeric($facet->id)){
						$ret = $facet->update();
					}else{
						$facet->libraryId = $this->libraryId;
						$facet->insert();
					}
				}
			}
			unset($this->facets);
		}
	}

	public function clearFacets(){
		$facets = new LibraryFacetSetting();
		$facets->libraryId = $this->libraryId;
		$facets->delete();
		$this->facets = array();
	}

	public function saveHolidays(){
		if (isset ($this->holidays) && is_array($this->holidays)){
			/** @var Holiday $holiday */
			foreach ($this->holidays as $holiday){
				if (isset($holiday->deleteOnSave) && $holiday->deleteOnSave == true){
					$holiday->delete();
				}else{
					if (isset($holiday->id) && is_numeric($holiday->id)){
						$holiday->update();
					}else{
						$holiday->libraryId = $this->libraryId;
						$holiday->insert();
					}
				}
			}
			unset($this->holidays);
		}
	}

	public function saveNearByBookStores(){
		if (isset ($this->nearbyBookStores) && is_array($this->nearbyBookStores)){
			/** @var NearbyBookStore $store */
			foreach ($this->nearbyBookStores as $store){
				if (isset($store->deleteOnSave) && $store->deleteOnSave == true){
					$store->delete();
				}else{
					if (isset($store->id) && is_numeric($store->id)){
						$store->update();
					}else{
						$store->libraryId = $this->libraryId;
						$store->insert();
					}
				}
			}
			unset($this->nearbyBookStores);
		}
	}

	static function getBookStores(){
		$library = Library::getActiveLibrary();
		if ($library) {
			return NearbyBookStore::getBookStores($library->libraryId);
		} else {
			return NearbyBookStore::getDefaultBookStores();
		}
	}

	static function getDefaultFacets($libraryId = -1){
		global $configArray;
		$defaultFacets = array();

		$facet = new LibraryFacetSetting();
		$facet->setupTopFacet('format_category', 'Format Category');
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;


		if ($configArray['Index']['enableDetailedAvailability']){
			$facet = new LibraryFacetSetting();
			$facet->setupTopFacet('availability_toggle', 'Available?', false);
			$facet->libraryId = $libraryId;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}


		if (!$configArray['Index']['enableDetailedAvailability']){
			$facet = new LibraryFacetSetting();
			$facet->setupSideFacet('available_at', 'Available Now At', false);
			$facet->libraryId = $libraryId;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('format', 'Format', false);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('literary_form_full', 'Literary Form', false);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('target_audience_full', 'Reading Level', false);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$facet->numEntriesToShowByDefault = 8;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('topic_facet', 'Subject', false);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('time_since_added', 'Added in the Last', false);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('authorStr', 'Author', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('awards_facet', 'Awards', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('econtent_device', 'Compatible Device', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('econtent_source', 'eContent Source', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('econtent_protection_type', 'eContent Protection', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('era', 'Era', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('genre_facet', 'Genre', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('itype', 'Item Type', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('language', 'Language', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('lexile_code', 'Lexile Code', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('lexile_score', 'Lexile Score', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('mpaa_rating', 'Movie Rating', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('institution', 'Owning System', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('building', 'Owning Branch', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('publishDate', 'Publication Date', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupAdvancedFacet('geographic_facet', 'Region', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new LibraryFacetSetting();
		$facet->setupSideFacet('rating_facet', 'User Rating', true);
		$facet->libraryId = $libraryId;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		return $defaultFacets;
	}
}