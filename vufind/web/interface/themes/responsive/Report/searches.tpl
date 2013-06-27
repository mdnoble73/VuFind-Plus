{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div class="row-fluid">
	{include file="Report/reportSwitcher.tpl"}
	{include file="Report/analyticsFilters.tpl"}
</div>

<div class="row-fluid">
	<div id="topSearchesContainer" class="span4">
		<h2>Top Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$topSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=topSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="topNoHitSearchesContainer" class="span4">
		<h2>Top No Hit Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$topNoHitSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=noHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="latestSearchesContainer" class="span4">
		<h2>Latest Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$latestSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=latestHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>

<div class="row-fluid">
	<div id="latestNoHitSearchesContainer" class="span4">
		<h2>Latest No Hit Searches</h2>
		<ol class='reportOrderedList'>
		{foreach from=$latestNoHitSearches item=searchTerm}
			<li>{$searchTerm}</li>
		{/foreach}
		</ol>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=latestNoHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="searchesByTypeContainer" class="span4">
		<div id="searchesByTypeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=searchesByType{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	<div id="searchesByScopeContainer" class="span4">
		<div id="searchesByScopeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=searchesByScope{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>

<div class="row-fluid">
	<div id="searchesWithFacetsContainer" class="span4">
		<div id="searchesWithFacetsChart" class="dashboardChart">
		</div>
	</div>
	<div id="facetUsageByTypeContainer" class="span4">
		<div id="facetUsageByTypeChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=facetUsageByType{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>
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