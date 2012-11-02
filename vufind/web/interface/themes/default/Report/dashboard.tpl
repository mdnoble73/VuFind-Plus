{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div id="page-content" class="content">
	{* include file="Report/analyticsFilters.tpl" *}
	<div id="activePageViewsContainer" class="reportContainer">
		<div id="activePageViewsChart" class="dashboardChart">
		</div>
	</div>
	<div id="activeUsersContainer" class="reportContainer">
		<div id="activeUsersChart" class="dashboardChart">
		</div>
	</div>
	<div id="activeSearchesContainer" class="reportContainer">
		<div id="activeSearchesChart" class="dashboardChart">
		</div>
	</div>
	<div id="activeEventsContainer" class="reportContainer">
		<div id="activeEventsChart" class="dashboardChart">
		</div>
	</div>
</div>
<div class="clearer"></div>
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}

$(document).ready(function() {
	setupRecentPageViewChart();
	setupRecentUsersChart();
	setupRecentSearchesChart();
	setupRecentEventsChart();
	getRecentActivity();
});
{/literal}
</script>
{/strip}