<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
		<h1>eContent Collection Summary</h1>
		<div class="statLine"><span class="statLabel">Number of eContent Files: </span><span class="statValue">{$collectionSummary.numTitles}</span></div>
		
		<h2>Number of Titles by Protection Type</h2>
		<table>
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
			
		<h2>Number of Titles by Source</h2>
		<table>
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
</div>