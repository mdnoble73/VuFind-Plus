{strip}
<div class="row-fluid">
	{* Narrow Search Options *}
	<div id="sidebar" class="span3">
		{* Display spelling suggestions if any *}
		{if $spellingSuggestions}
			<div class="sidegroup" id="spellingSuggestions">
				<h4>{translate text='spell_suggest'}</h4>
				<div class="sidegroupContents">
					<dl class="narrowList navmenu narrow_begin">
					{foreach from=$spellingSuggestions item=details key=term name=termLoop}
						<dd>{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}</dd>
					{/foreach}
					</dl>
				</div>
			</div>
		{/if}
			
		{if $sideRecommendations}
			{foreach from=$sideRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		{/if}
	</div>
	
	<div id="main-content" class="span9">
		{* Recommendations *}
		{if $topRecommendations}
			{foreach from=$topRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		{/if}
		{if $numUnscopedResults && $numUnscopedResults != $recordCount}
			<h2>No Results for </h2>
		{else}
			<h2>{translate text='nohit_heading'}</h2>
		{/if}
			
		<p class="error">{translate text='nohit_prefix'} - <b>{if $lookfor}{$lookfor|escape:"html"}{else}&lt;empty&gt;{/if}</b> - {translate text='nohit_suffix'}</p>
		
		{if $numUnscopedResults && $numUnscopedResults != $recordCount}
			<div class="unscopedResultCount">
				There are <b>{$numUnscopedResults}</b> results in the entire Marmot collection. <span style="font-size:15px"><a href="{$unscopedSearchUrl}">Search the entire collection.</a></span>
			</div>
		{/if}
		<div>
			{if $parseError}
				{$parseError}
			{/if}
			
			{if $spellingSuggestions}
				<div class="correction">{translate text='nohit_spelling'}:<br/>
				{foreach from=$spellingSuggestions item=details key=term name=termLoop}
					{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
				{/foreach}
				</div>
				<br/>
			{/if}
			
			{if $searchSuggestions}
				<div id="searchSuggestions">
					<h2>Similar Searches</h2>
					<p>These searches are similar to the search you tried. Would you like to try one of these instead?</p> 
					<ul> 
					{foreach from=$searchSuggestions item=suggestion}
						<li class="searchSuggestion"><a href="/Search/Results?lookfor={$suggestion.phrase|escape:url}&basicType={$searchIndex|escape:url}">{$suggestion.phrase}</a></li>
					{/foreach}
					</ul>
				</div>
			{/if}
			
			{if $unscopedResults}
				<h2>Results from the entire Marmot Catalog</h2>
				{foreach from=$unscopedResults item=record name="recordLoop"}
					<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
						{* This is raw HTML -- do not escape it: *}
						{$record}
					</div>
				{/foreach}
			{/if}

			{if $prospectorNumTitlesToLoad > 0}
				<script type="text/javascript">getProspectorResults({$prospectorNumTitlesToLoad}, {$prospectorSavedSearchId});</script>
				{* Prospector Results *}
				<div id='prospectorSearchResultsPlaceholder'></div>
			{/if}
			
			
			{* Display Repeat this search links *}
			{if strlen($lookfor) > 0 && count($repeatSearchOptions) > 0}
				<div class='repeatSearchHead'><h4>Try another catalog</h4></div>
					<div class='repeatSearchList'>
					{foreach from=$repeatSearchOptions item=repeatSearchOption}
						<div class='repeatSearchItem'>
							<a href="{$repeatSearchOption.link}" class='repeatSearchName' target='_blank'>{$repeatSearchOption.name}</a>{if $repeatSearchOption.description} - {$repeatSearchOption.description}{/if}
						</div>
					{/foreach}
				</div>
			{/if}

			{if $enableMaterialsRequest}
				<h2>Didn't find it?</h2>
				<p>Can't find what you are looking for? <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$lookfor}&basicType={$searchIndex}">Suggest a purchase</a>.</p>
			{/if}
			
			<div class="searchtools well">
				<strong>{translate text='Search Tools'}:</strong>
				<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
				{if $savedSearch}<a href="{$path}/MyResearch/SaveSearch?delete={$searchId}"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>{else}<a href="{$path}/MyResearch/SaveSearch?save={$searchId}"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>{/if}
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
		doGetStatusSummaries();
		{if $user}
		doGetSaveStatuses();
		{/if}
		doGetSeriesInfo();
		{literal} }); {/literal}
</script>
{/strip}