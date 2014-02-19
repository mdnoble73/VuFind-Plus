<ProspectorSearchResults>
<![CDATA[
	{if $prospectorResults}
		<div id='prospectorSearchResults'>
		  <div id='prospectorSearchResultsHeader'>
			  <img id='prospectorMan' src='{$path}/interface/themes/marmot/images/prospector_man.png'/>
			  <div id='prospectorSearchResultsTitle'>In Prospector</div>
			  <div id='prospectorSearchResultsNote'>Did you know that you can request items through Prospector and they will be delivered to your local library for pickup?</div>
			  <div class='clearfix'>&nbsp;</div>
		  </div>
			<div class="striped">
			  {foreach from=$prospectorResults item=prospectorResult}
			    <div class='result'>
				    <div class='resultItemLine1'><a class="title" href='{$prospectorResult.link}' rel="external" onclick="window.open (this.href, 'child'); return false">{$prospectorResult.title}</a></div>
				    <div class='resultItemLine2'>by {$prospectorResult.author} Published {$prospectorResult.pubDate}</div>
			    </div>
			  {/foreach}
				</div>
		  <div id='moreResultsFromProspector'><button class="btn btn-sm" onclick="window.open ('{$prospectorLink}', 'child'); return false">See all Results in Prospector</button></div>
		</div>
	{/if}
]]>
</ProspectorSearchResults>