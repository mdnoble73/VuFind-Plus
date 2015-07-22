{strip}
	<div id="main-content" class="col-md-12">
		<h3>Indexing Statistics ({$indexingStatsDate})</h3>
		
		<div id="reindexingStatsContainer">
			{if $noStatsFound}
				<div class="alert-warning">Sorry, we couldn't find any stats.</div>
			{else}
				<table class="table tablesorter table-condensed table-hover" id="reindexingStats">
					<thead>
						<tr>
							{foreach from=$indexingStatHeader item=itemHeader}
								<th>{$itemHeader}</th>
							{/foreach}
						</tr>
					</thead>
					<tbody>
					{foreach from=$indexingStats item=statsRow}
						<tr>
							{foreach from=$statsRow item=statCell}
								<td>{$statCell}</td>
							{/foreach}
						</tr>
					{/foreach}
					</tbody>
				</table>
			{/if}
		</div>
	</div>
{/strip}
<script type="text/javascript">
	{literal}
	$("#reindexingStats").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>