{assign var="curSection" value=-1}
{strip}
<div id="adminMenuAccordion">
	{if false && $user->hasRole('opacAdmin') }
	<h4><a href="#">General Configuration</a></h4>
	<div class="sidegroupContents">
		<div class="adminMenuLink {if $action == "Home"}active{/if}"><a href="{$path}/Admin/Home">Home</a></div>
		<div class="adminMenuLink {if $action == "Statistics"}active{/if}"><a href="{$path}/Admin/Statistics">Statistics</a></div>
		<div class="adminMenuLink {if $action == "Config"}active{/if}"><a href="{$path}/Admin/Config">Configuration</a>
			{if $action == "Config"}
			<ul style="padding-left:20px;">
				<div><a href="{$path}/Admin/Config?file=config.ini">General Configuration</a></div>
				<div><a href="{$path}/Admin/Config?file=searchspecs.yaml">Search Specifications</a></div>
				<div><a href="{$path}/Admin/Config?file=searches.ini">Search Settings</a></div>
				<div><a href="{$path}/Admin/Config?file=facets.ini">Facet Settings</a></div>
				<div><a href="{$path}/Admin/Config?file=stopwords.txt">Stop Words</a></div>
				<div><a href="{$path}/Admin/Config?file=synonyms.txt">Synonyms</a></div>
				<div><a href="{$path}/Admin/Config?file=protwords.txt">Protected Words</a></div>
				<div><a href="{$path}/Admin/Config?file=elevate.xml">Elevated Words</a></div>
			</ul>
			{/if}
		</div>
		<div class="adminMenuLink {if $action == "Maintenance"}active{/if}"><a href="{$path}/Admin/Maintenance">System Maintenance</a></div>
	</div>		
	{/if}
	
	{if $user && $user->hasRole('opacAdmin')}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">VuFind Configuration</a></h4>
	<div class="sidegroupContents">
		<div class="adminMenuLink {if $action == "IPAddresses"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></div>
		<div class="adminMenuLink {if $action == "Libraries"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/Libraries">Library Systems</a></div>
		<div class="adminMenuLink {if $action == "Locations"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/Locations">Locations</a></div>
		<div class="adminMenuLink {if $action == "ListWidgets"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/ListWidgets">List Widgets</a></div>
		<div class="adminMenuLink {if $action == "UserSuggestions"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/UserSuggestions">User Suggestions</a></div>
		<div class="adminMenuLink {if $action == "BookStores"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/BookStores">Book Stores</a></div>
		{if $ils == 'Millennium'}
			<div class="adminMenuLink {if $action == "CirculationStatuses"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/CirculationStatuses">Circulation Statuses</a></div>
			<div class="adminMenuLink {if $action == "NonHoldableLocations"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/NonHoldableLocations">Non-Holdable Locations</a></div>
			<div class="adminMenuLink {if $action == "PTypeRestrictedLocations"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/PTypeRestrictedLocations">PType Restricted Locations</a></div>
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
	</div>
	{/if}
	
	{if $user && ($user->hasRole('epubAdmin') || $user->hasRole('cataloging'))}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">eContent</a></h4>
	<div class="sidegroupContents">
		<div id="epubMenu">
			<div class="adminMenuLink"><span class="adminMenuHeader">Content Loading</span>
				<div class="adminMenuLink{if $action == "ListEPub"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Search/Results?type=Keyword&shard[]=eContent&lookfor=">Search Existing eContent</a></div>
				<div class="adminMenuLink{if $action == "NewEPub"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EcontentRecord/Edit">New eContent</a></div>
				<div class="adminMenuLink{if $action == "RecordDetectionSettings"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/RecordDetectionSettings">Automatic Import Settings</a></div>
				<div class="adminMenuLink{if $action == "ImportMarc"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/ImportEContentMarc">Import Marc Records</a></div>
				<div class="adminMenuLink{if $action == "MarcImportLog"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/MarcImportLog">Marc Record Import Log</a></div>
				{if $user->hasRole('epubAdmin')}
				<div class="adminMenuLink{if $action == "AttachEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/AttachEContent">Attach EContent To Records</a></div>
				<div class="adminMenuLink{if $action == "AttachEContentLog"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/AttachEContentLog">EContent Attachment Log</a></div>
				{/if}
			</div>
			<div class="adminMenuLink"><span class="adminMenuHeader">Reports</span>
				<div class="adminMenuLink{if $action == "EContentSummary"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentSummary">Collection Summary</a></div>
				<div class="adminMenuLink{if $action == "EContentCollection"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentCollection">Collection Details</a></div>
				{if $user->hasRole('epubAdmin')}
				<div class="adminMenuLink{if $action == "EContentUsage"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentUsage">Usage Statistics</a></div>
				{/if}
				<div class="adminMenuLink{if $action == "ItemlessEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/ItemlessEContent">Itemless eContent</a></div>
				<div class="adminMenuLink{if $action == "EContentPurchaseAlert"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentPurchaseAlert">Purchase Alert</a></div>
				<div class="adminMenuLink{if $action == "EContentTrialRecords"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentTrialRecords">Trial Records</a></div>
				<div class="adminMenuLink{if $action == "EContentWishList"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/EContentWishList">Wish List</a></div>
				<div class="adminMenuLink{if $action == "ArchivedEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/ArchivedEContent">Archived eContent</a></div>
				<div class="adminMenuLink{if $action == "DeletedEContent"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EContent/DeletedEContent">Deleted eContent</a></div>
			</div>
		</div>
	</div>
	{/if}
	
	{if $user && $user->hasRole('cataloging')}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">Materials Requests</a></h4>
	<div class="sidegroupContents">		
		<div class="adminMenuLink{if $action == "ManageRequests"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></div>
		<div class="adminMenuLink{if $action == "SummaryReport"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></div>
		<div class="adminMenuLink{if $action == "UserReport"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/MaterialsRequest/UserReport">Report By User</a></div>
		<div class="adminMenuLink{if $action == "ManageStatuses"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Admin/ManageStatuses">Manage Statuses</a></div>
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
	
	{if $user && $user->hasRole('opacAdmin')}
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">Editorial Reviews</a></h4>
	<div class="sidegroupContents">		
		<div class="adminMenuLink{if $action == "Edit" && $module == "EditorialReview"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EditorialReview/Edit">New Review</a></div>
		<div class="adminMenuLink{if $action == "Search" && $module == "EditorialReview"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></div>
	</div>
	
	{assign var="curSection" value=$curSection+1}
	<h4><a href="#">Reports</a></h4>
	<div class="sidegroupContents">		
		<div class="adminMenuLink{if $action == "ReportPurchase"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/ReportPurchase">Purchase Tracking</a></div>
		<div class="adminMenuLink{if $action == "ReportExternalLinks"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></div>
		<div class="adminMenuLink{if $action == "ReportPageViewsLocation"}{assign var="defaultSection" value=$curSection} active{/if}"><a href="{$path}/Report/ReportPageViewsLocation">Usage By Location</a></div>
	</div>
	{/if}
</div>
{/strip}
{literal}
<script type="text/javascript">
	$(function() {
		$("#adminMenuAccordion").accordion();
		{/literal}
		{if $defaultSection}
		$("#adminMenuAccordion").accordion("activate", {$defaultSection});
		{/if}
		{literal}
	});
</script>
{/literal}
