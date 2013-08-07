{assign var="curSection" value=-1}
{strip}
<div id="adminMenuAccordion">
	{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">VuFind Configuration</a></h4>
	<div class="sidegroupContents">
		{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
			<div class="adminMenuLink {if $action == "Libraries"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/Libraries">Library Systems</a></div>
			<div class="adminMenuLink {if $action == "Locations"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/Locations">Locations</a></div>
		{/if}
		{if $user->hasRole('opacAdmin')}
			<div class="adminMenuLink {if $action == "IPAddresses"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></div>
		{/if}
		<div class="adminMenuLink {if $action == "ListWidgets"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/ListWidgets">List Widgets</a></div>
		{if $user->hasRole('opacAdmin')}
			<div class="adminMenuLink {if $action == "UserSuggestions"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/UserSuggestions">User Suggestions</a></div>
			<div class="adminMenuLink {if $action == "BookStores"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/BookStores">Book Stores</a></div>
		{/if}
		{if $ils == 'Millennium' && $user->hasRole('opacAdmin')}
			<div class="adminMenuLink {if $action == "PTypes"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/PTypes">P-Types</a></div>
			<div class="adminMenuLink {if $action == "CirculationStatuses"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/CirculationStatuses">Circulation Statuses</a></div>
			<div class="adminMenuLink {if $action == "LoanRules"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/LoanRules">Loan Rules</a></div>
			<div class="adminMenuLink {if $action == "LoanRuleDeterminers"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/LoanRuleDeterminers">Loan Rule Determiners</a></div>
		{/if}
	</div>
	{/if}

	{if $user && $user->hasRole('userAdmin')}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">System Administration</a></h4>
	<div class="sidegroupContents">
		<div class="adminMenuLink {if $action == "Administrators"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/Administrators">Administrators</a></div>
		<div class="adminMenuLink {if $action == "TransferAccountInfo"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/TransferAccountInfo">Transfer Account Information</a></div>
		<div class="adminMenuLink {if $action == "DBMaintenance"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - VuFind</a></div>
		<div class="adminMenuLink {if $action == "DBMaintenanceEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></div>
		<div class="adminMenuLink {if $action == "CronLog"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/CronLog">Cron Log</a></div>
		<div class="adminMenuLink {if $action == "ReindexLog"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/ReindexLog">Reindex Log</a></div>
        <div class="adminMenuLink {if $action == "OverDriveExtract"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/OverDriveExtractLog">OverDrive Extract Log</a></div>
	</div>
	{/if}
	
	{if $user && ($user->hasRole('epubAdmin') || $user->hasRole('cataloging'))}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">eContent</a></h4>
	<div class="sidegroupContents">
		<div id="epubMenu">
			<div class="adminMenuLink{if $action == "ListEPub"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Search/Results?type=Keyword&amp;shard[]=eContent&amp;lookfor=">Search Existing eContent</a></div>
			<div class="adminMenuLink{if $action == "NewEPub"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EcontentRecord/Edit">New eContent</a></div>
			{if $user->hasRole('epubAdmin')}
			<div class="adminMenuLink{if $action == "AttachEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/AttachEContent">Attach EContent To Records</a></div>
			<div class="adminMenuLink{if $action == "AttachEContentLog"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/AttachEContentLog">EContent Attachment Log</a></div>
			{/if}
		</div>
	</div>
	{/if}
	
	{if $user && ($user->hasRole('cataloging') || $user->hasRole('library_material_requests'))}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">Materials Requests</a></h4>
	<div class="sidegroupContents">		
		<div class="adminMenuLink{if $action == "ManageRequests"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></div>
		<div class="adminMenuLink{if $action == "SummaryReport"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></div>
		<div class="adminMenuLink{if $action == "UserReport"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/MaterialsRequest/UserReport">Report By User</a></div>
		<div class="adminMenuLink{if $action == "ManageStatuses"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/ManageStatuses">Manage Statuses</a></div>
	</div>
	{/if}
	
	{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
		{assign var="curSection" value=$curSection+1}
		<h4><a href="#">Circulation</a></h4>
		<div class="sidegroupContents">	
			<div class="adminMenuLink{if $action == "Home" && $module == "Circa"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Circa/Home">Inventory</a></div>
			<div class="adminMenuLink{if $action == "OfflineCirculation" && $module == "Circa"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Circa/OfflineCirculation">Offline Circulation</a></div>
			<hr/>
			<div class="adminMenuLink{if $action == "OfflineHoldsReport" && $module == "Circa"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Circa/OfflineHoldsReport">Offline Holds Report</a></div>
			<div class="adminMenuLink{if $action == "OfflineCirculationReport" && $module == "Circa"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Circa/OfflineCirculationReport">Offline Circulation Report</a></div>
		</div>
	{/if}
	
	{if $user && $user->hasRole('genealogyContributor')}
		{assign var="curSection" value=$curSection+1}
		<h4><a href="#">Genealogy</a></h4>
		<div class="sidegroupContents">	
				<div class="adminMenuLink{if $action == "GenealogyImport"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/GenealogyImport">Import Information</a></div>
				<div class="adminMenuLink{if $action == "GenealogyFixDates"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/GenealogyFixDates">Fix Dates</a></div>
		</div>
	{/if}
	
	{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
		{assign var="curSection" value=$curSection+1}
		<h4><a href="#">Editorial Reviews</a></h4>
		<div class="sidegroupContents">		
			<div class="adminMenuLink{if $action == "Edit" && $module == "EditorialReview"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EditorialReview/Edit">New Review</a></div>
			<div class="adminMenuLink{if $action == "Search" && $module == "EditorialReview"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></div>
		</div>
		
		{assign var="curSection" value=$curSection+1}
		<h4><a href="#">Reports</a></h4>
		<div class="sidegroupContents">		
			<div class="adminMenuLink{if $action == "Dashboard"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/Dashboard">Dashboard</a></div>
			<div class="adminMenuLink{if $action == "Searches"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/Searches">Searches</a></div>
			<div class="adminMenuLink{if $action == "PageViews"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/PageViews">Page Views</a></div>
			<div class="adminMenuLink{if $action == "ILSIntegration"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/ILSIntegration">ILS Integration</a></div>
			<div class="adminMenuLink{if $action == "ReportPurchase"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/ReportPurchase">Purchase Tracking</a></div>
			<div class="adminMenuLink{if $action == "ReportExternalLinks"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></div>
		</div>
	{/if}
	
	{if $user && ($user->hasRole('epubAdmin') || $user->hasRole('cataloging') || $user->hasRole('libraryAdmin'))}
		{assign var="curSection" value=$curSection+1}
		<h4><a href="#">eContent Reports</a></h4>
		<div class="sidegroupContents">
			<div id="econtentReportMenu">
				<div class="adminMenuLink{if $action == "EContentSummary"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentSummary">Collection Summary</a></div>
				<div class="adminMenuLink{if $action == "EContentCollection"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentCollection">Collection Details</a></div>
				{if $user->hasRole('epubAdmin')}
				<div class="adminMenuLink{if $action == "EContentUsage"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentUsage">Usage Statistics</a></div>
				{/if}
				<div class="adminMenuLink{if $action == "ItemlessEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/ItemlessEContent">Itemless eContent</a></div>
				<div class="adminMenuLink{if $action == "EContentPurchaseAlert"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentPurchaseAlert">Purchase Alert</a></div>
				<div class="adminMenuLink{if $action == "EContentTrialRecords"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentTrialRecords">Trial Records</a></div>
				<div class="adminMenuLink{if $action == "EContentWishListReport"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentWishListReport">Wish List</a></div>
				{if $user->hasRole('epubAdmin')}
					<div class="adminMenuLink{if $action == "ArchivedEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/ArchivedEContent">Archived eContent</a></div>
					<div class="adminMenuLink{if $action == "DeletedEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/DeletedEContent">Deleted eContent</a></div>
					<div class="adminMenuLink{if $action == "EContentImportSummary"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentImportSummary">eContent Import Summary</a></div>
					<div class="adminMenuLink{if $action == "EContentImportDetails"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentImportDetails">eContent Import Details</a></div>
				{/if}
				{if $showPackagingDetailsReport && $user->hasRole('epubAdmin')}
					<div class="adminMenuLink{if $action == "PackagingSummary"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/PackagingSummary">ACS Packaging Summary</a></div>
					<div class="adminMenuLink{if $action == "PackagingDetails"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/PackagingDetails">ACS Packaging Details</a></div>
				{/if}
			</div>
		</div>
	{/if}
</div>
{/strip}
{literal}
<script type="text/javascript">
	$(function() {
		{/literal}
		var adminAccordion = $("#adminMenuAccordion");
		adminAccordion.accordion();
		{if $defaultSection}
			adminAccordion.accordion("option", "active", {$defaultSection});
		{/if}
		{literal}
	});
</script>
{/literal}
