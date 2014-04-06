{strip}
{if $user != false}
	{* Setup the accoridon *}
	<div id="home-account-links" class="sidebar-links row">
		<div class="panel-group" id="account-link-accordion">
			{* My Account *}
			<div class="panel {if $module == 'MyAccount' || $module == 'MyResearch' || ($module == 'Search' && $action == 'Home')}active{/if}">
				{* Clickable header for my account section *}
				<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myAccountPanel">
					<div class="panel-heading">
						<div class="panel-title">
							MY ACCOUNT
						</div>
					</div>
				</a>
				<div id="myAccountPanel" class="panel-collapse collapse {if $module == 'MyAccount' || $module == 'MyResearch' || ($module == 'Search' && $action == 'Home')}in{/if}">
					<div class="panel-body">
						{if $profile.finesval > 0 || $profile.expireclose}
							<div id="myAccountFines">
								{if $profile.finesval > 0}
									{if $showEcommerceLink && $profile.finesval > $minimumFineAmount}
										<div class="myAccountLink" style="color:red; font-weight:bold;">Your account has {$profile.fines} in fines.</div>
										<div class="myAccountLink"><a href='{$ecommerceLink}' >{if $payFinesLinkText}{$payFinesLinkText}{else}Click to Pay Fines Online{/if}</a></div>
									{else}
										<div class="myAccountLink" title="Please Contact your local library to pay fines or Charges." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to pay fines or Charges.')">Your account has {$profile.fines} in fines.</div>
									{/if}
								{/if}

								{if $profile.expireclose}<div class="myAccountLink"><a class ="alignright" title="Please contact your local library to have your library card renewed." style="color:green; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">Your library card will expire on {$profile.expires}.</a></div>{/if}
							</div>
							<br/>
						{/if}

						<div class="myAccountLink{if $pageTemplate=="checkedout.tpl"} active{/if}"><a href="{$path}/MyAccount/CheckedOut" id="checkedOut">Checked Out Titles ({$profile.numCheckedOutTotal})</a></div>
						<div class="myAccountLink{if $pageTemplate=="holds.tpl"} active{/if}"><a href="{$path}/MyAccount/Holds" id="holds">Titles On Hold ({$profile.numHoldsTotal})</a></div>

						{if $showFines}
						<div class="myAccountLink{if $pageTemplate=="fines.tpl"} active{/if}" title="Fines and account messages"><a href="{$path}/MyResearch/Fines">{translate text='Fines and Messages'}</a></div>
						{/if}
						{if $enableMaterialsRequest}
						<div class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="Materials Requests"><a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} ({$profile.numMaterialsRequests})</a></div>
						{/if}
						<div class="myAccountLink{if $pageTemplate=="readingHistory.tpl"} active{/if}"><a href="{$path}/MyAccount/ReadingHistory">My Reading History</a></div>
						<div class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyAccount/Profile">Account Settings</a></div>
						{* Only highlight saved searches as active if user is logged in: *}
						<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
					</div>
				</div>
			</div>

			{* My Lists*}
			<div class="panel {if $action == 'MyRatings' || $action == 'Suggested Titles' || $action == 'MyList'}active{/if}">
				<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myListsPanel">
					<div class="panel-heading">
						<div class="panel-title">
							MY LISTS
						</div>
					</div>
				</a>
				<div id="myListsPanel" class="panel-collapse collapse {if $action == 'MyRatings' || $action == 'Suggested Titles' || $action == 'MyList'}in{/if}">
					<div class="panel-body">
						<div class="myAccountLink"><a href="{$path}/MyAccount/MyRatings">{translate text='Titles You Rated'}</a></div>
						{foreach from=$lists item=list}
							{if $list.id != -1}
								<div class="myAccountLink"><a href="{$list.url}">{$list.name}{if $list.numTitles} ({$list.numTitles}){/if}</a></div>
							{/if}
						{/foreach}
					</div>
				</div>
			</div>

			{if $tagList}
				<div class="panel">
					<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myTagsPanel">
						<div class="panel-heading">
							<div class="panel-title">
								MY TAGS
							</div>
						</div>
					</a>
					<div id="myTagsPanel" class="panel-collapse collapse">
						<div class="panel-collapse">
							<div class="panel-body">
								{foreach from=$tagList item=tag}
									<div class="myAccountLink">
										<a href='{$path}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;basicType=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt})&nbsp;
										<a href='#' onclick="return VuFind.Account.removeTag('{$tag->tag}');">
											<span class="glyphicon glyphicon-remove-circle" title="Delete Tag">&nbsp;</span>
										</a>
									</div>
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			{/if}

			{* Admin Functionality if Available *}
			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
				{if in_array($action, array('Libraries', 'Locations', 'IPAddresses', 'ListWidgets', 'BrowseCategories', 'UserSuggestions', 'BookStores', 'PTypes', 'CirculationStatuses', 'LoanRules', 'LoanRuleDeterminers'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#vufindMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								VuFind Configuration
							</div>
						</div>
					</a>
					<div id="vufindMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
								<div class="adminMenuLink {if $action == "Libraries"}active{/if}"><a href="{$path}/Admin/Libraries">Library Systems</a></div>
								<div class="adminMenuLink {if $action == "Locations"}active{/if}"><a href="{$path}/Admin/Locations">Locations</a></div>
							{/if}
							{if $user->hasRole('opacAdmin')}
								<div class="adminMenuLink {if $action == "IPAddresses"}active{/if}"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></div>
							{/if}
							<div class="adminMenuLink {if $action == "ListWidgets"}active{/if}"><a href="{$path}/Admin/ListWidgets">List Widgets</a></div>
							<div class="adminMenuLink {if $action == "BrowseCategories"}active{/if}"><a href="{$path}/Admin/BrowseCategories">Browse Categories</a></div>
							{if $user->hasRole('opacAdmin')}
								<div class="adminMenuLink {if $action == "UserSuggestions"}active{/if}"><a href="{$path}/Admin/UserSuggestions">User Suggestions</a></div>
								<div class="adminMenuLink {if $action == "BookStores"}active{/if}"><a href="{$path}/Admin/BookStores">Book Stores</a></div>
							{/if}
							{if ($ils == 'Millennium' || $ils == 'Sierra') && $user->hasRole('opacAdmin')}
								<div class="adminMenuLink {if $action == "PTypes"}active{/if}"><a href="{$path}/Admin/PTypes">P-Types</a></div>
								<div class="adminMenuLink {if $action == "CirculationStatuses"}active{/if}"><a href="{$path}/Admin/CirculationStatuses">Circulation Statuses</a></div>
								<div class="adminMenuLink {if $action == "LoanRules"}active{/if}"><a href="{$path}/Admin/LoanRules">Loan Rules</a></div>
								<div class="adminMenuLink {if $action == "LoanRuleDeterminers"}active{/if}"><a href="{$path}/Admin/LoanRuleDeterminers">Loan Rule Determiners</a></div>
							{/if}
						</div>
					</div>
				</div>
			{/if}

			{if $user && $user->hasRole('userAdmin')}
				{if in_array($action, array('Administrators', 'TransferAccountInfo', 'DBMaintenance', 'DBMaintenanceEContent', 'CronLog', 'ReindexLog', 'OverDriveExtractLog'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#adminMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								System Administration
							</div>
						</div>
					</a>
					<div id="adminMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink {if $action == "Administrators"}active{/if}"><a href="{$path}/Admin/Administrators">Administrators</a></div>
							<div class="adminMenuLink {if $action == "TransferAccountInfo"}active{/if}"><a href="{$path}/Admin/TransferAccountInfo">Transfer Account Information</a></div>
							<div class="adminMenuLink {if $action == "DBMaintenance"}active{/if}"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - VuFind</a></div>
							<div class="adminMenuLink {if $action == "DBMaintenanceEContent"}active{/if}"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></div>
							<div class="adminMenuLink {if $module == 'Admin' && $action == "Home"}active{/if}"><a href="{$path}/Admin/Home">Solr Information</a></div>
							<div class="adminMenuLink {if $action == "CronLog"}active{/if}"><a href="{$path}/Admin/CronLog">Cron Log</a></div>
							<div class="adminMenuLink {if $action == "ReindexLog"}active{/if}"><a href="{$path}/Admin/ReindexLog">Reindex Log</a></div>
							<div class="adminMenuLink {if $action == "OverDriveExtractLog"}active{/if}"><a href="{$path}/Admin/OverDriveExtractLog">OverDrive Extract Log</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('cataloging') || $user->hasRole('library_material_requests'))}
				{if in_array($action, array('ManageRequests', 'SummaryReport', 'UserReport', 'ManageStatuses'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#materialsRequestMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Materials Requests
							</div>
						</div>
					</a>
					<div id="materialsRequestMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "ManageRequests"}active{/if}"><a href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></div>
							<div class="adminMenuLink{if $action == "SummaryReport"}active{/if}"><a href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></div>
							<div class="adminMenuLink{if $action == "UserReport"}active{/if}"><a href="{$path}/MaterialsRequest/UserReport">Report By User</a></div>
							<div class="adminMenuLink{if $action == "ManageStatuses"}active{/if}"><a href="{$path}/Admin/ManageStatuses">Manage Statuses</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
				{if $module == 'Circa'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#circulationMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Circulation
							</div>
						</div>
					</a>
					<div id="circulationMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "Home" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/Home">Inventory</a></div>
							<div class="adminMenuLink{if $action == "OfflineCirculation" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/OfflineCirculation">Offline Circulation</a></div>
							<div class="adminMenuLink{if $action == "OfflineHoldsReport" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/OfflineHoldsReport">Offline Holds Report</a></div>
							<div class="adminMenuLink{if $action == "OfflineCirculationReport" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/OfflineCirculationReport">Offline Circulation Report</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
				{if $module == "EditorialReview"}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#editorialReviewMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Editorial Reviews
							</div>
						</div>
					</a>
					<div id="editorialReviewMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "Edit" && $module == "EditorialReview"}active{/if}"><a href="{$path}/EditorialReview/Edit">New Review</a></div>
							<div class="adminMenuLink{if $action == "Search" && $module == "EditorialReview"}active{/if}"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></div>
						</div>
					</div>
				</div>

				{if in_array($action, array('Dashboard', 'Searches', 'PageViews', 'ILSIntegration', 'ReportPurchase', 'ReportExternalLinks', 'PatronStatus', 'DetailedReport'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#reportsMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Reports
							</div>
						</div>
					</a>
					<div id="reportsMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "Dashboard"}active{/if}"><a href="{$path}/Report/Dashboard">Dashboard</a></div>
							<div class="adminMenuLink{if $action == "Searches"}active{/if}"><a href="{$path}/Report/Searches">Searches</a></div>
							<div class="adminMenuLink{if $action == "PageViews"}active{/if}"><a href="{$path}/Report/PageViews">Page Views</a></div>
							<div class="adminMenuLink{if $action == "ILSIntegration"}active{/if}"><a href="{$path}/Report/ILSIntegration">ILS Integration</a></div>
							<div class="adminMenuLink{if $action == "ReportPurchase"}active{/if}"><a href="{$path}/Report/ReportPurchase">Purchase Tracking</a></div>
							<div class="adminMenuLink{if $action == "ReportExternalLinks"}active{/if}"><a href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></div>
							<div class="adminMenuLink{if $action == "PatronStatus"}active{/if}"><a href="{$path}/Report/PatronStatus">Patron Status</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('epubAdmin') || $user->hasRole('cataloging') || $user->hasRole('libraryAdmin'))}
				{if in_array($action, array('EContentSummary', 'EContentCollection', 'EContentUsage', 'ItemlessEContent', 'EContentPurchaseAlert', 'EContentTrialRecords',
				'EContentWishListReport', 'ArchivedEContent', 'DeletedEContent', 'EContentImportSummary', 'EContentImportDetails', 'PackagingSummary', 'PackagingDetails'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel">
					<a href="#econtentReportMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								eContent Reports
							</div>
						</div>
					</a>
					<div id="econtentReportMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "EContentSummary"}active{/if}"><a href="{$path}/EContent/EContentSummary">Collection Summary</a></div>
							<div class="adminMenuLink{if $action == "EContentCollection"}active{/if}"><a href="{$path}/EContent/EContentCollection">Collection Details</a></div>
							{if $user->hasRole('epubAdmin')}
								<div class="adminMenuLink{if $action == "EContentUsage"}active{/if}"><a href="{$path}/EContent/EContentUsage">Usage Statistics</a></div>
							{/if}
							<div class="adminMenuLink{if $action == "ItemlessEContent"}active{/if}"><a href="{$path}/EContent/ItemlessEContent">Itemless eContent</a></div>
							<div class="adminMenuLink{if $action == "EContentPurchaseAlert"}active{/if}"><a href="{$path}/EContent/EContentPurchaseAlert">Purchase Alert</a></div>
							<div class="adminMenuLink{if $action == "EContentTrialRecords"}active{/if}"><a href="{$path}/EContent/EContentTrialRecords">Trial Records</a></div>
							<div class="adminMenuLink{if $action == "EContentWishListReport"}active{/if}"><a href="{$path}/EContent/EContentWishListReport">Wish List</a></div>
							{if $user->hasRole('epubAdmin')}
								<div class="adminMenuLink{if $action == "ArchivedEContent"}active{/if}"><a href="{$path}/EContent/ArchivedEContent">Archived eContent</a></div>
								<div class="adminMenuLink{if $action == "DeletedEContent"}active{/if}"><a href="{$path}/EContent/DeletedEContent">Deleted eContent</a></div>
								<div class="adminMenuLink{if $action == "EContentImportSummary"}active{/if}"><a href="{$path}/EContent/EContentImportSummary">eContent Import Summary</a></div>
								<div class="adminMenuLink{if $action == "EContentImportDetails"}active{/if}"><a href="{$path}/EContent/EContentImportDetails">eContent Import Details</a></div>
							{/if}
							{if $showPackagingDetailsReport && $user->hasRole('epubAdmin')}
								<div class="adminMenuLink{if $action == "PackagingSummary"}active{/if}"><a href="{$path}/EContent/PackagingSummary">ACS Packaging Summary</a></div>
								<div class="adminMenuLink{if $action == "PackagingDetails"}active{/if}"><a href="{$path}/EContent/PackagingDetails">ACS Packaging Details</a></div>
							{/if}
						</div>
					</div>
				</div>
			{/if}
		</div>
	</div>
{/if}
{/strip}
