{strip}
<div id="filtersContainer" class="well">
	<h3>Filter by</h3>
	<form action="" method="get">
		{if $smarty.request.source}
			<input type="hidden" name="source" value="{$smarty.request.source}"/>
		{/if}
		<div id="filters">
			{if !isset($showDateFilters) || $showDateFilters == true}
			<div id="dateFilter">
				Include data <label for="startDateField">from</label> <input type="text" name="startDate" id="startDateField" value="{$startDate->format('m-d-Y')}"/> <label for="endDateField">to</label> <input type="text" name="endDate" id="endDateField" value="{$endDate->format('m-d-Y')}"/>.
			</div>
			{/if}
			{* Display existing filters *}
			{assign var=nextFilterIndex value=1}
			{foreach from=$activeFilters item=filterInfo key=filterIndex}
				<div class="filterSetting" id="filterSetting{$filterIndex}">
					<select name="filter[{$filterIndex}]" id="filter{$filterIndex}" data-filter-index="{$filterIndex}" onchange="showFilterValues(this)">
						<option value="">Select a value</option>
						{foreach from=$filters item=filter key=filterId}
							<option value="{$filter.field}" {if $filterInfo.name == $filter.field}selected="selected"{/if}>{$filter.label}</option>
						{/foreach}
					</select>
					{assign var=activeFilterName value=$filterInfo.name}
					{assign var=activeFilterValues value=$filters.$activeFilterName}
					<select class='filterValues' name='filterValue[{$filterIndex}]'>
						{foreach from=$activeFilterValues.values item=label key=value}
							<option value="{$value}" {if $filterInfo.value == $value}selected="selected"{/if}>{$label}</option>
						{/foreach}
					</select>
				</div>
				{assign var=nextFilterIndex value=$nextFilterIndex+1}
			{/foreach}
			{* Display a list of filters that can be applied *}
			<div class="filterSetting" id="filterSetting{$nextFilterIndex}">
				<select name="filter[{$nextFilterIndex}]" id="filter{$nextFilterIndex}" data-filter-index="{$nextFilterIndex}" onchange="showFilterValues(this)">
					<option value="">Select a value</option>
					{foreach from=$filters item=filter}
						<option value="{$filter.field}">{$filter.label}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div id="filterUpdate">
			<input type="submit" id="refreshReport" value="Refresh Report"/>
		</div>
	</form>
</div>
{/strip}
<script type="text/javascript">
	{literal}
	$("#startDateField").datepicker({dateFormat : 'mm-dd-yy'});
	$("#endDateField").datepicker({dateFormat : 'mm-dd-yy'});
	{/literal}
	var filterValues = {literal}{}{/literal};
	{foreach from=$filters item=filter}
		filterValues["{$filter.field}"] = {literal}{}{/literal};
		{foreach from=$filter.values item=label key=value}
			filterValues["{$filter.field}"]["{$value}"] = "{$label}";
		{/foreach}
	
	{/foreach}
	
	{* Cannot replace & since that causes the data to not load properly *}
	var filterParams = "{if $filterString}&{$filterString}{/if}";
</script>