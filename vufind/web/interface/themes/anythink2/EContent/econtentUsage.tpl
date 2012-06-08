<script type="text/javascript">
{literal}
$(function() {
    $( "#dateFilterStart" ).datepicker();
  });
$(function() {
  $( "#dateFilterEnd" ).datepicker();
});
{/literal}
</script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h2>eContent Usage</h2>
  
  <div id="filterContainer">
    <form id="statisticsForm" action="{$path}/EContent/EContentUsage">
      <div id="filterLeftColumn">
            <div id="startDate">
            Start Date: 
            <input id="dateFilterStart" name="dateFilterStart" value="{$selectedDateStart}" />
            </div>

            <div id="sourceFilterContainer">
            Source: <br/> 
            <select id="sourceFilter" name="sourceFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
                {section name=resultsSourceFilterRow loop=$resultsSourceFilter} 
                    <option value="{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}" {if !isset($selectedSourceFilter)}selected='selected' {elseif $resultsSourceFilter[resultsSourceFilterRow].SourceValue|in_array:$selectedSourceFilter}selected='selected'{/if}>{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}</option> 
                {/section} 
            </select>
            </div>
            <div id="minMaxPageViews">
            Total Usage: <br/> 
            Min: <input id="minPageViewsFilter" name="minPageViewsFilter" value="{$minUsage}" class="minmaxFilter" /> 
            Max: <input id="maxPageViewsFilter" name="maxPageViewsFilter" value="{$maxUsage}" class="minmaxFilter" />
            </div>

      </div>
      <div id="filterRightColumn">
            <div id="endDate">
            End Date: 
            <input id="dateFilterEnd" name="dateFilterEnd" value="{$selectedDateEnd}" />
            </div>
        
            <div id="accessType">
            Access Type: <br/> 
            <select id="accessTypeFilter" name="accessTypeFilter[]" multiple="multiple" size="5" class="multiSelectFilter" >
                {foreach from=$resultsAccessTypeFilter key=value item=label} 
                    <option value="{$value}" {if !isset($selectedAccessTypeFilter)}selected='selected' {elseif $value|in_array:$selectedAccessTypeFilter}selected='selected'{/if}>{$label}</option> 
                {/foreach} 
            </select> 
            </div>

      </div>

      <div class="divClear"></div>
      
      <div id="generateStats">
      <input type="submit" value="Generate Statistics"/>
      </div>
    
    </form>
  </div>
  
  <div id="reportSorting">
    {if $pageLinks.all}
      {translate text="Showing"}
      <b>{$recordStart}</b> - <b>{$recordEnd}</b>
      {translate text='of'} <b>{$recordCount}</b>
      {if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
    {/if}
              
    <b>Results Per Page: </b>
    <select name="itemsPerPage" id="itemsPerPage" onchange="this.form.submit();">
    {foreach from=$itemsPerPageList item=itemsPerPageItem key=keyName}
      <option value="{$itemsPerPageItem.amount}" {if $itemsPerPageItem.selected} selected="selected"{/if} >{$itemsPerPageItem.amount}</option>
    {/foreach}
    </select>
  </div>
  
  <table class="datatable">
  <thead>
  <tr>
  {foreach from=$usageSummary.columns key=fieldName item=label }
    <td>{$label}</td>
  {/foreach}
  </tr>
  
  </thead>
  <tbody>
  {foreach from=$usageSummary.data key=recordId item=usageInfo }
  <tr>
    {foreach from=$usageSummary.columns key=fieldName item=label }
    <td>{$usageInfo.$fieldName}</td>
    {/foreach}
  </tr>
  {/foreach}
  </tbody>
  </table>
  {if $pageLinks.all}<div class="pagination" id="pagination-bottom">Page: {$pageLinks.all}</div>{/if}
  
  <div class="exportButton">
  <input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" onclick="window.location.href='{$fullPath}{if strpos($fullPath, '?') > 0}&{else}?{/if}exportToExcel'">
  </div>
</div>
