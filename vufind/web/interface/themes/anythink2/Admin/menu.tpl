{if $user && (
     $user->hasRole('opacAdmin')
  || $user->hasRole('userAdmin')
  || $user->hasRole('epubAdmin')
  || $user->hasRole('cataloging')
  || $user->hasRole('genealogyContributor')
)}
  <h2>Administration</h2>

  {if $user && $user->hasRole('opacAdmin') }
  <h4>General Configuration</h4>
  <ul>
    <li><a {if $action == "Home"}class="active"{/if} href="{$path}/Admin/Home">Home</a></li>
    <li><a {if $action == "Statistics"}class="active"{/if} href="{$path}/Admin/Statistics">Statistics</a></li>
    <li><a {if $action == "Config"}class="active"{/if} href="{$path}/Admin/Config">Configuration</a>
      {if $action == "Config"}
      <ul>
        <li><a href="{$path}/Admin/Config?file=config.ini">General Configuration</a></li>
        <li><a href="{$path}/Admin/Config?file=searchspecs.yaml">Search Specifications</a></li>
        <li><a href="{$path}/Admin/Config?file=searches.ini">Search Settings</a></li>
        <li><a href="{$path}/Admin/Config?file=facets.ini">Facet Settings</a></li>
        <li><a href="{$path}/Admin/Config?file=stopwords.txt">Stop Words</a></li>
        <li><a href="{$path}/Admin/Config?file=synonyms.txt">Synonyms</a></li>
        <li><a href="{$path}/Admin/Config?file=protwords.txt">Protected Words</a></li>
        <li><a href="{$path}/Admin/Config?file=elevate.xml">Elevated Words</a></li>
      </ul>
      {/if}</li>
    <li><a {if $action == "Maintenance"}class="active"{/if} href="{$path}/Admin/Maintenance">System Maintenance</a></li>
  </ul>
  {/if}

  {if $user && $user->hasRole('opacAdmin')}
  <h4>VuFind Configuration</h4>
  <ul>
    <li><a {if $action == "IPAddresses"}class="active"{/if} href="{$path}/Admin/IPAddresses">IP Addresses</a></li>
    <li><a {if $action == "Libraries"}class="active"{/if} href="{$path}/Admin/Libraries">Library Systems</a></li>
    <li><a {if $action == "Locations"}class="active"{/if} href="{$path}/Admin/Locations">Locations</a></li>
    <li><a {if $action == "ListWidgets"}class="active"{/if} href="{$path}/Admin/ListWidgets">List Widgets</a></li>
    <li><a {if $action == "UserSuggestions"}class="active"{/if} href="{$path}/Admin/UserSuggestions">User Suggestions</a></li>
    {if $ils == 'Millennium'}
      <li><a {if $action == "CirculationStatuses"}class="active"{/if} href="{$path}/Admin/CirculationStatuses">Circulation Statuses</a></li>
      <li><a {if $action == "NonHoldableLocations"}class="active"{/if} href="{$path}/Admin/NonHoldableLocations">Non-Holdable Locations</a></li>
      <li><a {if $action == "PTypeRestrictedLocations"}class="active"{/if} href="{$path}/Admin/PTypeRestrictedLocations">PType Restricted Locations</a></li>
    {/if}
  </ul>
  {/if}

  {if $user && $user->hasRole('userAdmin')}
  <h4>System Administration</h4>
  <ul>
    <li><a {if $action == "Administrators"}class="active"{/if} href="{$path}/Admin/Administrators">Administrators</a></li>
    <li><a {if $action == "TransferAccountInfo"}class="active"{/if} href="{$path}/Admin/TransferAccountInfo">Transfer Account Information</a></li>
    <li><a {if $action == "DBMaintenance"}class="active"{/if} href="{$path}/Admin/DBMaintenance">DB Maintenance - VuFind</a></li>
    <li><a {if $action == "DBMaintenanceEContent"}class="active"{/if} href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></li>
    <li><a {if $action == "CronLog"}class="active"{/if} href="{$path}/Admin/CronLog">Cron Log</a></li>
    <li><a {if $action == "ReindexLog"}class="active"{/if} href="{$path}/Admin/ReindexLog">Reindex Log</a></li>
  </ul>
  {/if}

  {if $user && ($user->hasRole('epubAdmin') || $user->hasRole('cataloging'))}
  <h4>eContent</h4>
  <h5>Content Loading</h5>
  <ul>
    <li><a {if $action == "ListEPub"}class="active"{/if} href="{$path}/Search/Results?type=Keyword&shard[]=eContent&lookfor=">Search Existing eContent</a></li>
    <li><a {if $action == "NewEPub"}class="active"{/if} href="{$path}/EcontentRecord/Edit">New eContent</a></li>
    <li><a {if $action == "RecordDetectionSettings"}class="active"{/if} href="{$path}/EContent/RecordDetectionSettings">Automatic Import Settings</a></li>
    <li><a {if $action == "ImportMarc"}class="active"{/if} href="{$path}/EContent/ImportEContentMarc">Import Marc Records</a></li>
    <li><a {if $action == "MarcImportLog"}class="active"{/if} href="{$path}/EContent/MarcImportLog">Marc Record Import Log</a></li>
    {if $user->hasRole('epubAdmin')}
      <li><a {if $action == "AttachEContent"}class="active"{/if} href="{$path}/EContent/AttachEContent">Attach EContent To Records</a></li>
      <li><a {if $action == "AttachEContentLog"}class="active"{/if} href="{$path}/EContent/AttachEContentLog">EContent Attachment Log</a></li>
    {/if}
  </ul>
  <h5>Reports</h5>
  <ul>
    <li><a {if $action == "EContentSummary"}class="active"{/if} href="{$path}/EContent/EContentSummary">Collection Summary</a></li>
    <li><a {if $action == "EContentCollection"}class="active"{/if} href="{$path}/EContent/EContentCollection">Collection Details</a></li>
    {if $user->hasRole('epubAdmin')}
      <li><a {if $action == "EContentUsage"}class="active"{/if} href="{$path}/EContent/EContentUsage">Usage Statistics</a></li>
    {/if}
    <li><a {if $action == "ItemlessEContent"}class="active"{/if} href="{$path}/EContent/ItemlessEContent">Itemless eContent</a></li>
    <li><a {if $action == "EContentPurchaseAlert"}class="active"{/if} href="{$path}/EContent/EContentPurchaseAlert">Purchase Alert</a></li>
    <li><a {if $action == "EContentTrialRecords"}class="active"{/if} href="{$path}/EContent/EContentTrialRecords">Trial Records</a></li>
    <li><a {if $action == "EContentWishList"}class="active"{/if} href="{$path}/EContent/EContentWishList">Wish List</a></li>
    <li><a {if $action == "ArchivedEContent"}class="active"{/if} href="{$path}/EContent/ArchivedEContent">Archived eContent</a></li>
    <li><a {if $action == "DeletedEContent"}class="active"{/if} href="{$path}/EContent/DeletedEContent">Deleted eContent</a></li>
  </ul>
  {/if}

  {if $user && $user->hasRole('cataloging')}
  <h4>Materials Requests</h4>
  <ul>
    <li><a {if $action == "ManageRequests"}class="active"{/if} href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></li>
    <li><a {if $action == "SummaryReport"}class="active"{/if} href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></li>
    <li><a {if $action == "UserReport"}class="active"{/if} href="{$path}/MaterialsRequest/UserReport">Report By User</a></li>
    <li><a {if $action == "ManageStatuses"}class="active"{/if} href="{$path}/Admin/ManageStatuses">Manage Statuses</a></li>
  </ul>
  {/if}

  {if $user && $user->hasRole('genealogyContributor')}
  <h4>Genealogy</h4>
  <ul>
    <li><a {if $action == "GenealogyImport"}class="active"{/if} href="{$path}/Admin/GenealogyImport">Import Information</a></li>
    <li><a {if $action == "GenealogyFixDates"}class="active"{/if} href="{$path}/Admin/GenealogyFixDates">Fix Dates</a></li>
  </ul>
  {/if}

  {if $user && $user->hasRole('opacAdmin')}
  <h4>Editorial Reviews</h4>
  <ul>
    <li><a {if $action == "Edit" && $module == "EditorialReview"}class="active"{/if} href="{$path}/EditorialReview/Edit">New Review</a></li>
    <li><a {if $action == "Search" && $module == "EditorialReview"}class="active"{/if} href="{$path}/EditorialReview/Search">Search Existing Reviews</a></li>
  </ul>
  <h4>Reports</h4>
  <ul>
    <li><a {if $action == "ReportPurchase"}class="active"{/if} href="{$path}/Report/ReportPurchase">Purchase Tracking</a></li>
    <li><a {if $action == "ReportExternalLinks"}class="active"{/if} href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></li>
    <li><a {if $action == "ReportPageViewsLocation"}class="active"{/if} href="{$path}/Report/ReportPageViewsLocation">Usage By Location</a></li>
  </ul>
  {/if}
{/if}