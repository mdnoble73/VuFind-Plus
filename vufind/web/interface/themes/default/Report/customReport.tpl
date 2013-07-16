<script type="text/javascript" src="/js/highcharts/highcharts.js"></script>
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if}
	<div id="sidebar">
		{include file="Report/customReportSidebar.tpl"}
	</div>
	<div id="main-content">
		<h3>Custom Reporting</h3>
		<div class="page">
			<div id="reportPrompt">Please define your report using the options to the left.</div>
		</div>
	</div>
</div>