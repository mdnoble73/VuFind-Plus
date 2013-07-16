<div id="reportAccordion">
	<h4>{translate text="Report Type"}</h4>
	<div>
		<h5>Chart Type</h5>
		<input type="radio" name="reportType" id="reportTypeNone" value="none" checked="checked"/><label for="reportTypeNone">No Chart</label><br/>
		<input type="radio" name="reportType" id="reportTypeTrend" value="trend"/><label for="reportTypeTrend">Trend</label><br/>
		<input type="radio" name="reportType" id="reportTypeBar" value="barGraph"/><label for="reportTypeDashboard">Bar Graph</label><br/>
		<input type="radio" name="reportType" id="reportTypePie" value="pieChart"/><label for="reportTypeDashboard">Pie Chart</label><br/>
		<input type="radio" name="reportType" id="reportTypeLine" value="lineChart"/><label for="reportTypeDashboard">Line Chart</label><br/>
		<h5>Data</h5>
		<input type="checkbox" name="includeTable" id="reportTypeDashboard"/><label for="showTable">Show Raw Data</label><br/>
	</div>

	<h4>Data Options</h4>
	<div>
		<h5>Primary Axis</h5>
		<select name="secondaryAxis">
			<option value="pageViews">Page Views</option>
			<option value="events">Events</option>
			<option value="events">Searches</option>
		</select>
		<h5>Secondary Axis</h5>
		<select name="primaryAxis">
			<option value="time">Time</option>
			<option value="pageViews">Page Views</option>
			<option value="events">Events</option>
			<option value="events">Searches</option>
		</select>
		<h5>Trend Period</h5>
		<select name="trendPeriod">
			<option value="hour">Hour</option>
			<option value="day">Day</option>
			<option value="week">Week</option>
			<option value="month">Month</option>
			<option value="quarter">Month</option>
			<option value="year">Year</option>
			<option value="custom">Custom</option>
		</select>
		<input type="text" name="customTrendPeriod" />
	</div>

	<h4>Date Filters</h4>
	<div>
		<label>Starting From</label>
		<input type="text" name="startDate" id="startDateField" value="{$startDate->format('m-d-Y')}"/>
		<label for="endDateField">Ending On</label>
		<input type="text" name="endDate" id="endDateField" value="{$endDate->format('m-d-Y')}"/>
	</div>

	<h4>Session Filters</h4>
	<div>
		{* Display a list of filters that can be applied *}
		<div class="filterSetting" id="filterSetting{$nextFilterIndex}">
			<select name="filter[{$nextFilterIndex}]" id="filter{$nextFilterIndex}" data-filter-index="{$nextFilterIndex}" onchange="showFilterValues(this)">
				<option value="">Select a value</option>
				{foreach from=$filters item=filter}
					<option value="{$filter.field}">{$filter.label}</option>
				{/foreach}
			</select>
			<br/>
			<input type="button" name="addFilter" value="Add Filter" onclick="addFilter"/>
		</div>
	</div>
</div>
{literal}
<script type="text/javascript">
	$(function(){
		$("#reportAccordion").accordion();
		$("#startDateField").datepicker({dateFormat : 'mm-dd-yy'});
		$("#endDateField").datepicker({dateFormat : 'mm-dd-yy'});
	});
	var filterValues = {}{/literal};
	{foreach from=$filters item=filter}
	filterValues["{$filter.field}"] = {literal}{}{/literal};
	{foreach from=$filter.values item=label key=value}
	filterValues["{$filter.field}"]["{$value}"] = "{$label}";
	{/foreach}
	{/foreach}
	{* Cannot replace & since that causes the data to not load properly *}
	var filterParams = "{if $filterString}&{$filterString}{/if}";
	{literal}
</script>
{/literal}