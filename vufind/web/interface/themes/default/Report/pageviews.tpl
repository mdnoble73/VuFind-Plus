{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div id="page-content" class="content">
	<div id="pageViewsByModuleContainer" class="reportContainer">
		<div id="pageViewsByModuleChart" class="dashboardChart">
		</div>
	</div>

	<div id="pageViewsByModuleContainer" class="reportContainer2">
		<h2>Slowest Average Pageviews</h2>
		<table id="reportTable">
			<thead>
				<tr><th>Module</th><th>Action</th><th>Method</th><th>Speed</th><th>Num Page Views</th></tr>
				{foreach from=$slowPages item=pageView}
					<tr><td>{$pageView.module}</td><td>{$pageView.action}</td><td>{$pageView.method}</td><td>{$pageView.loadTime}</td><td>{$pageView.numViews}</td></tr>
				{/foreach}
			</thead>
		</table>
	</div>

	<div id="pageViewsByThemeContainer" class="reportContainer">
		<div id="pageViewsByThemeChart" class="dashboardChart">
		</div>
	</div>
	
	<div id="pageViewsByDeviceContainer" class="reportContainer">
		<div id="pageViewsByDeviceChart" class="dashboardChart">
		</div>
	</div>
	
	<div id="pageViewsByHomeLocationContainer" class="reportContainer">
		<div id="pageViewsByHomeLocationChart" class="dashboardChart">
		</div>
	</div>
</div>
<div class="clearer"></div>
{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}

$(document).ready(function() {
	setupPageViewsByModuleChart();
	setupPageViewsByThemeChart();
	setupPageViewsByDeviceChart();
	setupPageViewsByHomeLocationChart();
});
{/literal}
</script>