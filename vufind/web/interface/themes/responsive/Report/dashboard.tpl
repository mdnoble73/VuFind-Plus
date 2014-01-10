{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div class="row">
	{include file="Report/reportSwitcher.tpl"}
	{include file="Report/analyticsFilters.tpl"}
</div>
<div class="row">
	<div id="activePageViewsContainer" class="reportContainer col-md-6">
		<div id="activePageViewsChart" class="dashboardChart">
		</div>
	</div>
	<div id="activeUsersContainer" class="reportContainer col-md-6">
		<div id="activeUsersChart" class="dashboardChart">
		</div>
	</div>
</div>
<div class="row">
	<div id="activeSearchesContainer" class="reportContainer col-md-6">
		<div id="activeSearchesChart" class="dashboardChart">
		</div>
	</div>
	<div id="activeEventsContainer" class="reportContainer col-md-6">
		<div id="activeEventsChart" class="dashboardChart">
		</div>
	</div>
</div>
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}

var activePageViewChart = setupInteractiveChart('activePageViewsChart', 'Page Views', 'Time', 'Count');
var recentUsersChart = setupInteractiveChart('activeUsersChart', 'Users', 'Time', 'Count');
var recentSearchesChart = setupInteractiveChart('activeSearchesChart', 'Searches', 'Time', 'Count');
var recentEventsChart = setupInteractiveChart('activeEventsChart', 'Events', 'Time', 'Count');

$(document).ready(function() {
	getRecentActivity();
});
{/literal}
</script>
{/strip}