{strip}
<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<div class="row">
	{include file="Report/analyticsFilters.tpl"}
</div>

<h3 class="clearer">Holds</h3>
<div class="row">
	{* Holds by Result*}
	<div id="holdsByResultContainer" class="col-md-4">
		<div id="holdsByResultChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=holdsByResult{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	{* Sessions by number of holds placed (1, 2, 3, 4, etc) *}
	<div id="holdsPerSessionContainer" class="col-md-4">
		<div id="holdsBySessionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=holdsPerSession{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	{* Sessions by number of holds canceled (1, 2, 3, 4, etc) *}
	<div id="holdsCancelledPerSessionContainer" class="col-md-4">
		<div id="holdsCancelledBySessionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=holdsCancelledPerSession{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
</div>

<div class="row">
	{* Sessions by number of holds updated (1, 2, 3, 4, etc) *}
	<div id="holdsUpdatedPerSessionContainer" class="col-md-4">
		<div id="holdsUpdatedBySessionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=holdsUpdatedPerSession{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	{* Sessions by number of holds failed holds (1, 2, 3, 4, etc) *}
	<div id="holdsFailedPerSessionContainer" class="col-md-4">
		<div id="holdsFailedBySessionChart" class="dashboardChart">
		</div>
		<div class="detailedReportLink"><a href="/Report/DetailedReport?source=holdsFailedPerSession{if $filterString}&amp;{$filterString|replace:"&":"&amp;"}{/if}">Detailed Report</a></div>
	</div>
	{* Top Titles with Failed Holds *}
	{* Holds by Patron Type *}
	{* Holds by Home Libary *}
	{* Holds by Home Libary *}
</div>

<h3 class="clearer">Renewals</h3>
{* Sessions by number of renewals (1, 2, 3, 4, etc) *}
{* Sessions by number of failed renewals (1, 2, 3, 4, etc) *}
<h3 class="clearer">Reading History</h3>
{* Sessions with Reading History Updates *}
{* Sessions with Reading History View *}
{* Trend of *}
<h3 class="clearer">Profile</h3>
{* Sessions with Profile View *}
{* Sessions with Profile Update *}
<h3 class="clearer">Self Registration</h3>

{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}

$(document).ready(function() {
	VuFind.AnalyticReports.setupPieChart("holdsByResultChart", "holdsByResult", "Holds By Result", "% Used");
	VuFind.AnalyticReports.setupPieChart("holdsBySessionChart", "holdsPerSession", "Holds Per Session", "% Used");
	VuFind.AnalyticReports.setupPieChart("holdsCancelledBySessionChart", "holdsCancelledPerSession", "Holds Cancelled Per Session", "% Used");
	VuFind.AnalyticReports.setupPieChart("holdsUpdatedBySessionChart", "holdsUpdatedPerSession", "Holds Updated Per Session", "% Used");
	VuFind.AnalyticReports.setupPieChart("holdsFailedBySessionChart", "holdsFailedPerSession", "Holds Failed Per Session", "% Used");
});
{/literal}
</script>