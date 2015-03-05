{strip}
  <div id="main-content" class="col-md-12">
		<h2>eContent Collection Summary</h2>
		<div class="statLine"><span class="statLabel">Number of eContent Files: </span><span class="statValue">{$collectionSummary.numTitles}</span></div>
		
		<h4>Number of Titles by Protection Type</h4>
		<table class="table table-striped table-hover">
			<thead>
			<tr><th>Protection Type</th><th>Number of Titles</th></tr>
			</thead>
			<tbody>
			{foreach from=$collectionSummary.statsByDRM key=hasDRM item=numTitles }
			<tr>
			<td>{if $hasDRM == 'free'}Free Titles{elseif $hasDRM == 'singleUse'}Single Use Titles{else}ACS Titles{/if}</td>
			<td>{$numTitles}</td>
			</tr>
			{/foreach}
			</tbody>
		</table>
			
		<h4>Number of Titles by Source</h4>
		<table class="table table-striped table-hover">
			<thead>
			<tr><th>Source</th><th>Number of Titles</th></tr>
			</thead>
			<tbody>
			{foreach from=$collectionSummary.statsBySource key=source item=numTitles }
			<tr>
			<td>{if $source}{$source}{else}Unset{/if}</td>
			<td>{$numTitles}</td>
			</tr>
			{/foreach}
			</tbody>
		</table>
  </div>
{/strip}