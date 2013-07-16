{strip}
<div data-role="page" id="Search-list-none">
	{include file="header.tpl"}
	<div data-role="content">
		<p>{translate text='nohit_prefix'} - <strong>{$lookfor|escape}</strong> - {translate text='nohit_suffix'}</p>
		
		{if $numUnscopedResults && $numUnscopedResults != $recordCount}
			<div class="unscopedResultCount">
				There are <b>{$numUnscopedResults}</b> results in the entire Marmot collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>
			</div>
		{/if}
		
		{if $spellingSuggestions}
			<h3 class="correction">{translate text='nohit_spelling'}:</h3>
			<ul>
			{foreach from=$spellingSuggestions item=details key=term name=termLoop}
				<li>{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}</li>{/if}
			{/foreach}
			</ul>
		{/if}
		
		{if $searchSuggestions}
			<div id="searchSuggestions">
				<h3>Similar Searches</h3>
				<p>These searches are similar to the search you tried. Would you like to try one of these instead?</p> 
				<ul> 
				{foreach from=$searchSuggestions item=suggestion}
					<li class="searchSuggestion"><a href="/Search/Results?lookfor={$suggestion.phrase|escape:url}&basicType={$searchIndex|escape:url}">{$suggestion.phrase}</a></li>
				{/foreach}
				</ul>
			</div>
		{/if}
			
		{if $unscopedResults}
			<h3>Results from the entire Marmot Catalog</h3>
			{foreach from=$unscopedResults item=record name="recordLoop"}
				<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
					{* This is raw HTML -- do not escape it: *}
					{$record}
				</div>
			{/foreach}
		{/if}
	</div>
	
	{include file="footer.tpl"}
</div>
{/strip}