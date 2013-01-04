{strip}
<script type="text/javascript" src="/js/analyticReports.js"></script>
<div id="page-content" class="content">
	{include file="Report/analyticsFilters.tpl"}
	<h3>{$reportData.name}</h3>
	<table id="reportData" class="tablesorter reportDataTable">
		<thead>
			<tr>
				{foreach from=$reportData.columns item=columnName}
					<th>{$columnName}</th>
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach from=$reportData.data item=dataRow}
				<tr>
					{foreach from=$dataRow item=dataVal}
						<td>{$dataVal}</td>
					{/foreach}
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
<div class="clearer"></div>
{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}
	$(document).ready(function() {
		$("#reportData").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader'});
	});
{/literal}
</script>