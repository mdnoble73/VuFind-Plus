{strip}
	<div id="main-content" class="col-md-12">
		<h3>Reindex Stats</h3>
		
		<div id="reindexingStatsContainer">
			{if $noStatsFound}
				<div class="alert-warning">Sorry, we couldn't find any stats.</div>
			{else}
				<table class="table table-condensed table-hover">
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