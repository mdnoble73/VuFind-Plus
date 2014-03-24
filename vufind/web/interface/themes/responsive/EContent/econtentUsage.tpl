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
{strip}
  <div id="main-content" class="col-md-12">
	  <div class="row">
		  <div class="col-xs-12">
				<h2>eContent Usage</h2>
		  </div>
	  </div>
		
		<div id="filterContainer" class="row">
			<div class="col-xs-12">
				<form id="statisticsForm" action="{$path}/EContent/EContentUsage" role="form">
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label for="dateFilterStart">Start Date</label>
								<input id="dateFilterStart" name="dateFilterStart" value="{$selectedDateStart}" class="form-control"/>
							</div>
							<div class="form-group">
								<label for="dateFilterEnd">End Date</label>
								<input id="dateFilterEnd" name="dateFilterEnd" value="{$selectedDateEnd}"  class="form-control"/>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="form-group">
								<label for="minPageViewsFilter">Min Usage</label>
								<input id="minPageViewsFilter" name="minPageViewsFilter" value="{$minUsage}" class="form-control" />
							</div>
							<div class="form-group">
								<label for="maxPageViewsFilter">Max Usage</label>
								<input id="maxPageViewsFilter" name="maxPageViewsFilter" value="{$maxUsage}" class="form-control" />
							</div>
						</div>

					</div>
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label for="sourceFilter">Source</label>
								<select id="sourceFilter" name="sourceFilter[]" multiple="multiple" size="5" class="form-control">
									{section name=resultsSourceFilterRow loop=$resultsSourceFilter}
										<option value="{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}" {if !isset($selectedSourceFilter)}selected='selected' {elseif $resultsSourceFilter[resultsSourceFilterRow].SourceValue|in_array:$selectedSourceFilter}selected='selected'{/if}>{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}</option>
									{/section}
								</select>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="form-group">
								<label for="accessTypeFilter">Access Type</label>
								<select id="accessTypeFilter" name="accessTypeFilter[]" multiple="multiple" size="5" class="form-control" >
									{foreach from=$resultsAccessTypeFilter key=value item=label}
										<option value="{$value}" {if !isset($selectedAccessTypeFilter)}selected='selected' {elseif $value|in_array:$selectedAccessTypeFilter}selected='selected'{/if}>{$label}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<input type="submit" value="Generate Statistics" class="btn btn-sm btn-info"/>
						</div>
					</div>
	      </form>
			</div>
		</div>
    
		<div id="reportSorting" class="row">
			<div class="col-xs-12">
				{if $pageLinks.all}
					{translate text="Showing"}
					<b>{$recordStart}</b> - <b>{$recordEnd}</b>
					{translate text='of'} <b>{$recordCount}</b>
					{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
				{/if}

				<label for="itemsPerPage">Results Per Page: </label>
				<select name="itemsPerPage" id="itemsPerPage" onchange="this.form.submit();">
				{foreach from=$itemsPerPageList item=itemsPerPageItem key=keyName}
					<option value="{$itemsPerPageItem.amount}" {if $itemsPerPageItem.selected} selected="selected"{/if} >{$itemsPerPageItem.amount}</option>
				{/foreach}
			  </select>
			</div>
		</div>

	  <div class="row">
		  <div class="col-xs-12">
				<table class="table table-bordered table-striped">
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
		  </div>
	  </div>
	  {if $pageLinks.all}
		  <div class="pagination row" id="pagination-bottom">
				<div class="col-xs-12">
			    Page: {$pageLinks.all}
				</div>
		  </div>
	  {/if}

	  <div class="row">
		  <div class="col-xs-12">
		    <input class="btn btn-sm btn-default" type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" onclick="window.location.href='{$fullPath}{if strpos($fullPath, '?') > 0}&{else}?{/if}exportToExcel'">
			</div>
	  </div>
	</div>
{/strip}