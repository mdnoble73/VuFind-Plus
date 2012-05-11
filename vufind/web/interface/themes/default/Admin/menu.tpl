{strip}
{if false && $user->hasRole('opacAdmin') }
<div class="sidegroup">
	<h4>General Configuration</h4>
	<ul id="generalMenu">
		<li{if $action == "Home"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Home">Home</a></li>
		<li{if $action == "Statistics"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Statistics">Statistics</a></li>
		<li{if $action == "Config"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Config">Configuration</a>
			{if $action == "Config"}
			<ul style="padding-left:20px;">
				<li><a href="{$path}/Admin/Config?file=config.ini">General Configuration</a></li>
				<li><a href="{$path}/Admin/Config?file=searchspecs.yaml">Search Specifications</a></li>
				<li><a href="{$path}/Admin/Config?file=searches.ini">Search Settings</a></li>
				<li><a href="{$path}/Admin/Config?file=facets.ini">Facet Settings</a></li>
				<li><a href="{$path}/Admin/Config?file=stopwords.txt">Stop Words</a></li>
				<li><a href="{$path}/Admin/Config?file=synonyms.txt">Synonyms</a></li>
				<li><a href="{$path}/Admin/Config?file=protwords.txt">Protected Words</a></li>
				<li><a href="{$path}/Admin/Config?file=elevate.xml">Elevated Words</a></li>
			</ul>
			{/if}
		</li>
		<li{if $action == "Maintenance"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Maintenance">System Maintenance</a></li>
	</ul>
</div>		
{/if}

{if $user && ($user->hasRole('epubAdmin') || $user->hasRole('cataloging'))}
<div class="sidegroup">		
	<h4>eContent</h4>
	<div id="epubMenu">
		<div class="myAccountLink">Content Loading
			<div class="myAccountLink{if $action == "ListEPub"} active{/if}" style="float: none;"><a href="{$path}/Search/Results?type=Keyword&shard[]=eContent&lookfor=">Search Existing eContent</a></div>
			<div class="myAccountLink{if $action == "NewEPub"} active{/if}" style="float: none;"><a href="{$path}/EcontentRecord/Edit">New eContent</a></div>
			<div class="myAccountLink{if $action == "RecordDetectionSettings"} active{/if}" style="float: none;"><a href="{$path}/EContent/RecordDetectionSettings">Automatic Import Settings</a></div>
			<div class="myAccountLink{if $action == "ImportMarc"} active{/if}" style="float: none;"><a href="{$path}/EContent/ImportEContentMarc">Import Marc Records</a></div>
			<div class="myAccountLink{if $action == "MarcImportLog"} active{/if}" style="float: none;"><a href="{$path}/EContent/MarcImportLog">Marc Record Import Log</a></div>
			{if $user->hasRole('epubAdmin')}
			<div class="myAccountLink{if $action == "AttachEContent"} active{/if}" style="float: none;"><a href="{$path}/EContent/AttachEContent">Attach EContent To Records</a></div>
			<div class="myAccountLink{if $action == "AttachEContentLog"} active{/if}" style="float: none;"><a href="{$path}/EContent/AttachEContentLog">EContent Attachment Log</a></div>
			{/if}
		</div>
		<div class="myAccountLink">Reports
			<div class="myAccountLink{if $action == "EContentSummary"} active{/if}" style="float: none;"><a href="{$path}/EContent/EContentSummary">Collection Summary</a></div>
			<div class="myAccountLink{if $action == "EContentCollection"} active{/if}" style="float: none;"><a href="{$path}/EContent/EContentCollection">Collection Details</a></div>
			{if $user->hasRole('epubAdmin')}
			<div class="myAccountLink{if $action == "EContentUsage"} active{/if}" style="float: none;"><a href="{$path}/EContent/EContentUsage">Usage Statistics</a></div>
			{/if}
			<div class="myAccountLink{if $action == "ItemlessEContent"} active{/if}" style="float: none;"><a href="{$path}/EContent/ItemlessEContent">Itemless eContent</a></div>
			<div class="myAccountLink{if $action == "EContentPurchaseAlert"} active{/if}" style="float: none;"><a href="{$path}/EContent/EContentPurchaseAlert">Purchase Alert</a></div>
			<div class="myAccountLink{if $action == "EContentTrialRecords"} active{/if}" style="float: none;"><a href="{$path}/EContent/EContentTrialRecords">Trial Records</a></div>
			<div class="myAccountLink{if $action == "EContentWishList"} active{/if}" style="float: none;"><a href="{$path}/EContent/EContentWishList">Wish List</a></div>
			<div class="myAccountLink{if $action == "ArchivedEContent"} active{/if}" style="float: none;"><a href="{$path}/EContent/ArchivedEContent">Archived eContent</a></div>
			<div class="myAccountLink{if $action == "DeletedEContent"} active{/if}" style="float: none;"><a href="{$path}/EContent/DeletedEContent">Deleted eContent</a></div>
		</div>
	</div>
</div>
{/if}

{if $user && $user->hasRole('cataloging')}
<div class="sidegroup">		
	<h4>Materials Requests</h4>
	<ul id="generalMenu">
		<li style="float: none;"><a href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></li>
		<li style="float: none;"><a href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></li>
		<li style="float: none;"><a href="{$path}/MaterialsRequest/UserReport">Report By User</a></li>
		<li style="float: none;"><a href="{$path}/Admin/ManageStatuses">Manage Statuses</a></li>
	</ul>
</div>
{/if}

{if $user && $user->hasRole('genealogyContributor')}
<div class="sidegroup">	
	<h4>Genealogy</h4>
	<ul id="genealogyMenu">
		<li{if $action == "GenealogyImport"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/GenealogyImport">Import Information</a></li>
		<li{if $action == "GenealogyFixDates"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/GenealogyFixDates">Fix Dates</a></li>
	</ul>
	</div>
{/if}

{if $user && $user->hasRole('opacAdmin')}
<div class="sidegroup">		
		<h4>Editorial Reviews</h4>
		<ul id="editorialReviewMenu">
			<li style="float: none;"><a href="{$path}/EditorialReview/Edit">New Review</a></li>
			<li style="float: none;"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></li>
		</ul>
</div>

<div class="sidegroup">		
		<h4>Reports</h4>
		<ul id="editorialReviewMenu">
			<li style="float: none;"><a href="{$path}/Report/ReportPurchase">Purchase Tracking</a></li>
			<li style="float: none;"><a href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></li>
			<li style="float: none;"><a href="{$path}/Report/ReportPageViewsLocation">Usage By Location</a></li>
		</ul>
</div>
{/if}

{if $user && ($user->hasRole('userAdmin') || $user->hasRole('opacAdmin'))}
<div class="sidegroup">		
	<h4>VuFind Configuration</h4>
	<ul id="vufindMenu">
		{if $user->hasRole('userAdmin') }
		<li{if $action == "Administrators"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Administrators">Administrators</a></li>
		<li{if $action == "TransferAccountInfo"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/TransferAccountInfo">Transfer Account Information</a></li>
		<li{if $action == "DBMaintenance"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - VuFind</a></li>
		<li{if $action == "DBMaintenanceEContent"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></li>
		<li{if $action == "CronLog"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/CronLog">Cron Log</a></li>
		<li{if $action == "ReindexLog"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/ReindexLog">Reindex Log</a></li>
		{/if}
		{if $user->hasRole('opacAdmin') }
		<li{if $action == "IPAddresses"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></li>
		<li{if $action == "Libraries"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Libraries">Library Systems</a></li>
		<li{if $action == "Locations"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/Locations">Locations</a></li>
		<li{if $action == "ListWidgets"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/ListWidgets">List Widgets</a></li>
		<li{if $action == "UserSuggestions"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/UserSuggestions">User Suggestions</a></li>
		{if $ils == 'Millennium'}
			<li{if $action == "CirculationStatuses"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/CirculationStatuses">Circulation Statuses</a></li>
			<li{if $action == "NonHoldableLocations"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/NonHoldableLocations">Non-Holdable Locations</a></li>
			<li{if $action == "PTypeRestrictedLocations"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/PTypeRestrictedLocations">PType Restricted Locations</a></li>
		{/if}
		{/if}
	</ul>
</div>
{/if}{/strip}