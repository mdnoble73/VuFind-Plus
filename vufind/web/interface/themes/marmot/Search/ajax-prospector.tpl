<ProspectorSearchResults>
<![CDATA[
	{if $prospectorResults}
		<div id='prospectorSearchResults'>
		  <div id='prospectorSearchResultsHeader'>
		  <img id='prospectorMan' src='{$path}/interface/themes/marmot/images/prospector_man.png'/>
		  <div id='prospectorSearchResultsTitle'>In Prospector</div>
		  <div id='prospectorSearchResultsNote'>Did you know that you can request items through Prospector and they will be delivered to your local library for pickup?</div>
		  </div>
		  <div class='clearer'>&nbsp;</div>
		  {foreach from=$prospectorResults item=prospectorResult}
		    <div class='result'>
		    <div class='resultItemLine1'><a class="title" href='{$prospectorResult.link}' target="_blank">{$prospectorResult.title}</a></div>
		    <div class='resultItemLine2'>by {$prospectorResult.author} Published {$prospectorResult.pubDate}</div>
		    </div>
		  {/foreach}
		  <div id='moreResultsFromProsplector'><a href='{$prospectorLink}' target="_blank">See all Results in Prospector</a></div>
		</div>
	{/if}
]]>
</ProspectorSearchResults>