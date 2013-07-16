{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div id="page-content" class="content">
	{include file="Report/reportSwitcher.tpl"}
	{include file="Report/analyticsFilters.tpl"}
	<div id="topSearchesContainer" class="reportContainer">
		<h2>Top Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$topSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=topSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="topNoHitSearchesContainer" class="reportContainer">
		<h2>Top No Hit Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$topNoHitSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=noHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="latestSearchesContainer" class="reportContainer">
		<h2>Latest Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$latestSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=latestHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="latestNoHitSearchesContainer" class="reportContainer">
		<h2>Latest No Hit Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$latestNoHitSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=latestNoHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="searchesByTypeContainer" class="reportContainer">
		<div id="searchesByTypeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=searchesByType{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="searchesByScopeContainer" class="reportContainer">
		<div id="searchesByScopeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=searchesByScope{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="searchesWithFacetsContainer" class="reportContainer">
		<div id="searchesWithFacetsChart" class="dashboardChart">
		</div>
	</div>
	<div id="facetUsageByTypeContainer" class="reportContainer">
		<div id="facetUsageByTypeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=facetUsageByType{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>
<div class="clearer"></div>
{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}
$(document).ready(function() {
	setupPieChart("searchesByTypeChart", 'searchesByType', 'Searches By Type', '% Used');
	setupPieChart("searchesByScopeChart", 'searchesByScope', 'Searches By Scope', '% Used');
	setupPieChart("searchesWithFacetsChart", 'searchesWithFacets', 'Searches with Facets', '% Used');
	setupPieChart("facetUsageByTypeChart", 'facetUsageByType', 'Facets By Type', '% Used');
});
{/literal}
</script>