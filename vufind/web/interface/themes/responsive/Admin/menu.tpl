{assign var="curSection" value=-1}
{strip}
<div id="adminMenuAccordion" class="accordion">
	{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
		{if in_array($action, array('Libraries', 'Locations', 'IPAddresses', 'ListWidgets', 'UserSuggestions', 'BookStores', 'PTypes', 'CirculationStatuses', 'LoanRules', 'LoanRuleDeterminers'))}
			{assign var="curSection" value=true}
		{else}
			{assign var="curSection" value=false}
		{/if}
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#vufindMenuGroup" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">VuFind Configuration</a>
			</div>
			<div id="vufindMenuGroup" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
						<div class="adminMenuLink {if $action == "Libraries"}active{/if}"><a href="{$path}/Admin/Libraries">Library Systems</a></div>
						<div class="adminMenuLink {if $action == "Locations"}active{/if}"><a href="{$path}/Admin/Locations">Locations</a></div>
					{/if}
					{if $user->hasRole('opacAdmin')}
						<div class="adminMenuLink {if $action == "IPAddresses"}active{/if}"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></div>
					{/if}
					<div class="adminMenuLink {if $action == "ListWidgets"}active{/if}"><a href="{$path}/Admin/ListWidgets">List Widgets</a></div>
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
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#adminMenuGroup" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">System Administration</a>
			</div>
			<div id="adminMenuGroup" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					<div class="adminMenuLink {if $action == "Administrators"}active{/if}"><a href="{$path}/Admin/Administrators">Administrators</a></div>
					<div class="adminMenuLink {if $action == "TransferAccountInfo"}active{/if}"><a href="{$path}/Admin/TransferAccountInfo">Transfer Account Information</a></div>
					<div class="adminMenuLink {if $action == "DBMaintenance"}active{/if}"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - VuFind</a></div>
					<div class="adminMenuLink {if $action == "DBMaintenanceEContent"}active{/if}"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></div>
					<div class="adminMenuLink {if $action == "CronLog"}active{/if}"><a href="{$path}/Admin/CronLog">Cron Log</a></div>
					<div class="adminMenuLink {if $action == "ReindexLog"}active{/if}"><a href="{$path}/Admin/ReindexLog">Reindex Log</a></div>
		      <div class="adminMenuLink {if $action == "OverDriveExtractLog"}active{/if}"><a href="{$path}/Admin/OverDriveExtractLog">OverDrive Extract Log</a></div>
				</div>
			</div>
		</div>
	{/if}
	
	{if $user && ($user->hasRole('epubAdmin'))}
		{if in_array($action, array('AttachEContent', 'AttachEContentLog'))}
			{assign var="curSection" value=true}
		{else}
			{assign var="curSection" value=false}
		{/if}
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#eContentMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">eContent</a>
			</div>
			<div id="eContentMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					{*
					<div class="adminMenuLink{if $action == "NewEPub"}active{/if}"><a href="{$path}/EcontentRecord/Edit">New eContent</a></div>
					*}
					<div class="adminMenuLink{if $action == "AttachEContent"}active{/if}"><a href="{$path}/EContent/AttachEContent">Attach EContent To Records</a></div>
					<div class="adminMenuLink{if $action == "AttachEContentLog"}active{/if}"><a href="{$path}/EContent/AttachEContentLog">EContent Attachment Log</a></div>
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
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#materialsRequestMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">Materials Requests</a>
			</div>
			<div id="materialsRequestMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
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
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#circulationMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">Circulation</a>
			</div>
			<div id="circulationMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					<div class="adminMenuLink{if $action == "Home" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/Home">Inventory</a></div>
				</div>
			</div>
		</div>
	{/if}
	
	{if $user && $user->hasRole('genealogyContributor')}
		{if in_array($action, array('GenealogyImport', 'GenealogyFixDates', 'GenealogyReindex'))}
			{assign var="curSection" value=true}
		{else}
			{assign var="curSection" value=false}
		{/if}
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#genealogyMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">Genealogy</a>
			</div>
			<div id="genealogyMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					<div class="adminMenuLink{if $action == "GenealogyImport"}active{/if}"><a href="{$path}/Admin/GenealogyImport">Import Information</a></div>
					<div class="adminMenuLink{if $action == "GenealogyReindex"}active{/if}"><a href="{$path}/Admin/GenealogyReindex">Reindex</a></div>
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
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#editorialReviewMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">Editorial Reviews</a>
			</div>
			<div id="editorialReviewMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					<div class="adminMenuLink{if $action == "Edit" && $module == "EditorialReview"}active{/if}"><a href="{$path}/EditorialReview/Edit">New Review</a></div>
					<div class="adminMenuLink{if $action == "Search" && $module == "EditorialReview"}active{/if}"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></div>
				</div>
			</div>
		</div>

		{if in_array($action, array('Dashboard', 'Searches', 'PageViews', 'ILSIntegration', 'ReportPurchase', 'ReportExternalLinks'))}
			{assign var="curSection" value=true}
		{else}
			{assign var="curSection" value=false}
		{/if}
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#reportsMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">Reports</a>
			</div>
			<div id="reportsMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
					<div class="adminMenuLink{if $action == "Dashboard"}active{/if}"><a href="{$path}/Report/Dashboard">Dashboard</a></div>
					<div class="adminMenuLink{if $action == "Searches"}active{/if}"><a href="{$path}/Report/Searches">Searches</a></div>
					<div class="adminMenuLink{if $action == "PageViews"}active{/if}"><a href="{$path}/Report/PageViews">Page Views</a></div>
					<div class="adminMenuLink{if $action == "ILSIntegration"}active{/if}"><a href="{$path}/Report/ILSIntegration">ILS Integration</a></div>
					<div class="adminMenuLink{if $action == "ReportPurchase"}active{/if}"><a href="{$path}/Report/ReportPurchase">Purchase Tracking</a></div>
					<div class="adminMenuLink{if $action == "ReportExternalLinks"}active{/if}"><a href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></div>
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
		<div class="accordion-group">
			<div class="accordion-heading">
				<a href="#econtentReportMenu" class="accordion-toggle" data-toggle="collapse" data-parent="#adminMenuAccordion">eContent Reports</a>
			</div>
			<div id="econtentReportMenu" class="accordion-body collapse {if $curSection}in{/if}">
				<div class="accordion-inner">
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
{/strip}
