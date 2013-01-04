{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>

<div id="page-content" class="content">
	{include file="Report/reportSwitcher.tpl"}
	{include file="Report/analyticsFilters.tpl"}
	<h2 class="clearer">Holds</h2>
	{* Holds by Result*}
	<div id="holdsByResultContainer" class="reportContainer">
		<div id="holdsByResultChart" class="dashboardChart">
		</div>
	</div>
	{* Sessions by number of holds placed (1, 2, 3, 4, etc) *}
	<div id="holdsPerSessionContainer" class="reportContainer">
		<div id="holdsBySessionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=holdsPerSession{if $filterString}&amp;{$filterStringg|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	{* Sessions by number of holds canceled (1, 2, 3, 4, etc) *}
	{* Sessions by number of holds updated (1, 2, 3, 4, etc) *}
	{* Sessions by number of holds failed holds (1, 2, 3, 4, etc) *}
	{* Top Titles with Failed Holds *}
	{* Holds by Patron Type *}
	{* Holds by Home Libary *}
	{* Holds by Home Libary *}

	<h2 class="clearer">Renewals</h2>
	{* Sessions by number of renewals (1, 2, 3, 4, etc) *}
	{* Sessions by number of failed renewals (1, 2, 3, 4, etc) *}
	<h2 class="clearer">Reading History</h2>
	{* Sessions with Reading History Updates *}
	{* Sessions with Reading History View *}
	{* Trend of *}
	<h2 class="clearer">Profile</h2>
	{* Sessions with Profile View *}
	{* Sessions with Profile Update *}
	<h2 class="clearer">Self Registration</h2>
</div>
<div class="clearer"></div>
{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}

$(document).ready(function() {
	setupPieChart("holdsByResultChart", "holdsByResult", "Holds By Result", "% Used");
	setupPieChart("holdsBySessionChart", "holdsPerSession", "Holds Per Session", "% Used");
});
{/literal}
</script>