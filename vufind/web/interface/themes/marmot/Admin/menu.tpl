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
    
    
    {if $user && $user->hasRole('epubAdmin')}
		<div class="sidegroup">    
			<h4>eContent</h4>
			<div id="epubMenu">
				<div class="myAccountLink">Content Loading
					<div class="myAccountLink{if $action == "ListEPub"} active{/if}" style="float: none;"><a href="{$path}/Search/Results?type=Keyword&shard[]=eContent&lookfor=">Search Existing eContent</a></div>
					<div class="myAccountLink{if $action == "NewEPub"} active{/if}" style="float: none;"><a href="{$path}/EcontentRecord/Edit">New eContent</a></div>
					<div class="myAccountLink{if $action == "ImportMarc"} active{/if}" style="float: none;"><a href="{$path}/Admin/ImportEContentMarc">Import Marc Records</a></div>
					<div class="myAccountLink{if $action == "MarcImportLog"} active{/if}" style="float: none;"><a href="{$path}/Admin/MarcImportLog">Marc Record Import Log</a></div>
					<div class="myAccountLink{if $action == "AttachEContent"} active{/if}" style="float: none;"><a href="{$path}/Admin/AttachEContent">Attach EContent To Records</a></div>
					<div class="myAccountLink{if $action == "AttachEContentLog"} active{/if}" style="float: none;"><a href="{$path}/Admin/AttachEContentLog">EContent Attachment Log</a></div>
				</div>
				<div class="myAccountLink">Reports
					<div class="myAccountLink{if $action == "EContentSummary"} active{/if}" style="float: none;"><a href="{$path}/Admin/EContentSummary">Collection Summary</a></div>
					<div class="myAccountLink{if $action == "EContentUsage"} active{/if}" style="float: none;"><a href="{$path}/Admin/EContentUsage">Usage Statistics</a></div>
					<div class="myAccountLink{if $action == "ItemlessEContent"} active{/if}" style="float: none;"><a href="{$path}/Admin/ItemlessEContent">Itemless eContent</a></div>
					<div class="myAccountLink{if $action == "EContentPurchaseAlert"} active{/if}" style="float: none;"><a href="{$path}/Admin/EContentPurchaseAlert">Purchase Alert</a></div>
					<div class="myAccountLink{if $action == "EContentTrialRecords"} active{/if}" style="float: none;"><a href="{$path}/Admin/EContentTrialRecords">Trial Records</a></div>
					<div class="myAccountLink{if $action == "EContentWishList"} active{/if}" style="float: none;"><a href="{$path}/Admin/EContentWishList">Wish List</a></div>
					<div class="myAccountLink{if $action == "ArchivedEContent"} active{/if}" style="float: none;"><a href="{$path}/Admin/ArchivedEContent">Archived eContent</a></div>
					<div class="myAccountLink{if $action == "DeletedEContent"} active{/if}" style="float: none;"><a href="{$path}/Admin/DeletedEContent">Deleted eContent</a></div>
				</div>
			</div>
		</div>
		{/if}

    {if $user && $user->hasRole('genealogyContributor')}
    <div class="sidegroup">  
    <h3>Genealogy</h3>
    <ul id="list3">
      <li{if $action == "People"} class="active"{/if} style="float: none;"><a href="People">People</a></li>
      {*<li{if $action == "Artifacts"} class="active"{/if} style="float: none;"><a href="People">Artifacts</a></li>*}
      <li{if $action == "GenealogyImport"} class="active"{/if} style="float: none;"><a href="GenealogyImport">Import Information</a></li>
      <li{if $action == "GenealogyReindex"} class="active"{/if} style="float: none;"><a href="GenealogyReindex">Reindex Information</a></li>
      <li{if $action == "GenealogyFixDates"} class="active"{/if} style="float: none;"><a href="GenealogyFixDates">Fix Dates</a></li>
      
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
    <h3>Marmot Configuration</h3>
    <ul id="list2">
      {if $user->hasRole('userAdmin') }
      <li{if $action == "Administrators"} class="active"{/if} style="float: none;"><a href="{$path}/Administrators">Administrators</a></li>
      <li{if $action == "DBMaintenance"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - VuFind</a></li>
      <li{if $action == "DBMaintenanceEContent"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></li>
      {/if}
      {if $user->hasRole('opacAdmin') }
      <li{if $action == "CirculationStatuses"} class="active"{/if} style="float: none;"><a href="{$path}/CirculationStatuses">Circulation Statuses</a></li>
      <li{if $action == "IPAddresses"} class="active"{/if} style="float: none;"><a href="{$path}/IPAddresses">IP Addresses</a></li>
      <li{if $action == "Libraries"} class="active"{/if} style="float: none;"><a href="{$path}/Libraries">Library Systems</a></li>
      <li{if $action == "Locations"} class="active"{/if} style="float: none;"><a href="{$path}/Locations">Locations</a></li>
      <li{if $action == "NonHoldableLocations"} class="active"{/if} style="float: none;"><a href="{$path}/NonHoldableLocations">Non-Holdable Locations</a></li>
      <li{if $action == "PTypeRestrictedLocations"} class="active"{/if} style="float: none;"><a href="{$path}/PTypeRestrictedLocations">PType Restricted Locations</a></li>
      <li{if $action == "ListWidgets"} class="active"{/if} style="float: none;"><a href="{$path}/Admin/ListWidgets">List Widgets</a></li>
      <li{if $action == "UserSuggestions"} class="active"{/if} style="float: none;"><a href="{$path}/UserSuggestions">User Suggestions</a></li>
      {/if}
    </ul>
</div>
{/if}{/strip}