{strip}
	<div id="page-content" class="content">
		{include file="Report/analyticsFilters.tpl"}
		<h2>{$reportData.name}</h2>
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
{/strip}
{* Setup charts for rendering*}
<script type="text/javascript">
{literal}
	$(document).ready(function() {
		$("#reportData").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader'});
	});
{/literal}
</script>