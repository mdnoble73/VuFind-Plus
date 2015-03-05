{strip}
	<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
	<div class="row">
		{include file="Report/analyticsFilters.tpl"}
	</div>
	<div class="row">
		<div id="topSearchesContainer" class="col-md-4">
			<h3>Top Searches</h3>
			<ol class='reportOrderedList'>
			{foreach from=$topSearches item=searchTerm}
				<li>{$searchTerm}</li>
			{/foreach}
			</ol>
			<div class="detailedReportLink"><a href="/Report/DetailedReport?source=topSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
		</div>
		<div id="topNoHitSearchesContainer" class="col-md-4">
			<h3>Top No Hit Searches</h3>
			<ol class='reportOrderedList'>
			{foreach from=$topNoHitSearches item=searchTerm}
				<li>{$searchTerm}</li>
			{/foreach}
			</ol>
			<div class="detailedReportLink"><a href="/Report/DetailedReport?source=noHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
		</div>
		<div id="latestSearchesContainer" class="col-md-4">
			<h3>Latest Searches</h3>
			<ol class='reportOrderedList'>
			{foreach from=$latestSearches item=searchTerm}
				<li>{$searchTerm}</li>
			{/foreach}
			</ol>
			<div class="detailedReportLink"><a href="/Report/DetailedReport?source=latestHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
		</div>
	</div>

	<div class="row">
		<div id="latestNoHitSearchesContainer" class="col-md-4">
			<h3>Latest No Hit Searches</h3>
			<ol class='reportOrderedList'>
			{foreach from=$latestNoHitSearches item=searchTerm}
				<li>{$searchTerm}</li>
			{/foreach}
			</ol>
			<div class="detailedReportLink"><a href="/Report/DetailedReport?source=latestNoHitSearches{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
		</div>
		<div id="searchesByTypeContainer" class="col-md-4">
			<div id="searchesByTypeChart" class="dashboardChart">
			</div>
			<div class="detailedReportLink"><a href="/Report/DetailedReport?source=searchesByType{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
		</div>
		<div id="searchesByScopeContainer" class="col-md-4">
			<div id="searchesByScopeChart" class="dashboardChart">
			</div>
			<div class="detailedReportLink"><a href="/Report/DetailedReport?source=searchesByScope{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
		</div>
	</div>

	<div class="row">
		<div id="searchesWithFacetsContainer" class="col-md-4">
			<div id="searchesWithFacetsChart" class="dashboardChart">
			</div>
		</div>
		<div id="facetUsageByTypeContainer" class="col-md-4">
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
	VuFind.AnalyticReports.setupPieChart("searchesByTypeChart", 'searchesByType', 'Searches By Type', '% Used');
	VuFind.AnalyticReports.setupPieChart("searchesByScopeChart", 'searchesByScope', 'Searches By Scope', '% Used');
	VuFind.AnalyticReports.setupPieChart("searchesWithFacetsChart", 'searchesWithFacets', 'Searches with Facets', '% Used');
	VuFind.AnalyticReports.setupPieChart("facetUsageByTypeChart", 'facetUsageByType', 'Facets By Type', '% Used');
});
{/literal}
</script>