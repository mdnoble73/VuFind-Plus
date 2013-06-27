{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>

<div class="row-fluid">
	{include file="Report/reportSwitcher.tpl"}
	{include file="Report/analyticsFilters.tpl"}
</div>

<div class="row-fluid">
	<div id="pageViewsByModuleContainer" class="span4">
		<div id="pageViewsByModuleChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByModule{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>

	<div id="slowestPageViewsContainer" class="span8">
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

<div class="row-fluid">
	<div id="pageViewsByModuleActionContainer" class="span4">
		<div id="pageViewsByModuleActionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByModuleAction{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>

	<div id="pageViewsByThemeContainer" class="span4">
		<div id="pageViewsByThemeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByTheme{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	
	<div id="pageViewsByDeviceContainer" class="span4">
		<div id="pageViewsByDeviceChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByDevice{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>

<div class="row-fluid">
	<div id="pageViewsByHomeLocationContainer" class="span4">
		<div id="pageViewsByHomeLocationChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=pageViewsByHomeLocation{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	
	<div id="pageViewsByPhysicalLocationContainer" class="span4">
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
	setupBarChart("pageViewsByModuleChart", "pageViewsByModule", "Page Views By Module", "Module", "Page Views");
	setupBarChart("pageViewsByModuleActionChart", "pageViewsByModuleAction", "Page Views By Module & Action", "Module & Action", "Page Views");
	setupBarChart("pageViewsByThemeChart", "pageViewsByTheme", "Page Views By Theme", "Theme", "Page Views");
	setupBarChart("pageViewsByDeviceChart", "pageViewsByDevice", "Page Views By Device", "Device", "Page Views");
	setupPieChart("pageViewsByHomeLocationChart", "pageViewsByHomeLocation", "Page Views By Home Location", "Home Location", "Page Views");
	setupPieChart("pageViewsByPhysicalLocationChart", "pageViewsByPhysicalLocation", "Page Views By Physical Location", "Physical Location", "Page Views");
});
{/literal}
</script>