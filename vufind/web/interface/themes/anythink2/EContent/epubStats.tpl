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
<script type="text/javascript">
{literal}
  $(document).ready(function(){
    if ($('#statType').val() == 'usageSummary'){
      $('#filterContainer').show();
    }else{
      $('#filterContainer').hide();
    }
    $('#statType').change(function(){
      if ($(this).val() == 'usageSummary') {
        $('#filterContainer').show();
      }else{
        $('#filterContainer').hide();
      }
      $('#statisticsDisplay').hide();
    });
  });
{/literal}
</script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>E-Pub Usage Statistics</h1>

  <div id="statisticsContainer">
    <form id="statisticsForm" action="{$path}/Admin/EPubStats">
      <div id="statisticsFormDiv">
        <label for="statType">Statistic To Show</label>
        <select name="statType" id="statType">
          <option value="collectionSummary" {if ($statType == "collectionSummary")} selected="selected" {/if}>Collection Summary</option>
          <option value="usageSummary" {if ($statType == "usageSummary")} selected="selected" {/if}>eContent Usage</option>
          <!--
          <option value="titleSummary">Title Summary</option>
          <option value="patronSummary">Patron Summary</option>
          <option value="individualTitles">Individual Titles</option>
           -->
        </select>

  <div id="filterContainer">

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
      </div>
      <div id="generateStats">
      <input type="submit" value="Generate Statistics"/>
      </div>

  </div>
  </form>
    <div id="statisticsDisplay">
      {include file="$stats_template"}
    </div>
  </div>
</div>
