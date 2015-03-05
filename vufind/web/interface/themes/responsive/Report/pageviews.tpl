{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<div class="row">
	{include file="Report/analyticsFilters.tpl"}
</div>

<div class="row">
	<div id="pageViewsByModuleContainer" class="col-md-4">
		<div id="pageViewsByModuleChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByModule{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>

	<div id="slowestPageViewsContainer" class="col-md-8">
		<h2>Slowest Average Pageviews</h2>
		<table id="reportTable">
			<thead>
				<tr><th>Module</th><th>Action</th><th>Method</th><th>Speed</th><th>Num Page Views</th></tr>
			</thead>
			<tbody>
				{foreach from=$slowPages item=pageView}
					<tr><td>{$pageView.module}</td><td>{$pageView.action}</td><td>{$pageView.method}</td><td>{$pageView.loadTime}</td><td>{$pageView.numViews}</td></tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>

<div class="row">
	<div id="pageViewsByModuleActionContainer" class="col-md-4">
		<div id="pageViewsByModuleActionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByModuleAction{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>

	<div id="pageViewsByThemeContainer" class="col-md-4">
		<div id="pageViewsByThemeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByTheme{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	
	<div id="pageViewsByDeviceContainer" class="col-md-4">
		<div id="pageViewsByDeviceChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByDevice{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>

<div class="row">
	<div id="pageViewsByHomeLocationContainer" class="col-md-4">
		<div id="pageViewsByHomeLocationChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByHomeLocation{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	
	<div id="pageViewsByPhysicalLocationContainer" class="col-md-4">
		<div id="pageViewsByPhysicalLocationChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByPhysicalLocation{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>

{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}

$(document).ready(function() {
	VuFind.AnalyticReports.setupBarChart("pageViewsByModuleChart", "pageViewsByModule", "Page Views By Module", "Module", "Page Views");
	VuFind.AnalyticReports.setupBarChart("pageViewsByModuleActionChart", "pageViewsByModuleAction", "Page Views By Module & Action", "Module & Action", "Page Views");
	VuFind.AnalyticReports.setupBarChart("pageViewsByThemeChart", "pageViewsByTheme", "Page Views By Theme", "Theme", "Page Views");
	VuFind.AnalyticReports.setupBarChart("pageViewsByDeviceChart", "pageViewsByDevice", "Page Views By Device", "Device", "Page Views");
	VuFind.AnalyticReports.setupPieChart("pageViewsByHomeLocationChart", "pageViewsByHomeLocation", "Page Views By Home Location", "Home Location", "Page Views");
	VuFind.AnalyticReports.setupPieChart("pageViewsByPhysicalLocationChart", "pageViewsByPhysicalLocation", "Page Views By Physical Location", "Physical Location", "Page Views");
});
{/literal}
</script>