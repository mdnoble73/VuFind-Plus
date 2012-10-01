<div id="page-content" class="content">
	{* Narrow Search Options *}
	<div id="sidebar">
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
	
	<div id="main-content">
		{* Recommendations *}
		{if $topRecommendations}
			{foreach from=$topRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		{/if}
		<div class="resulthead"><h3>{translate text='nohit_heading'}</h3></div>
			
			<p class="error">{translate text='nohit_prefix'} - <b>{$lookfor|escape:"html"}</b> - {translate text='nohit_suffix'}</p>

		<div>
		<ul id="noResultsSuggest">
		<li>Check the spelling of your search terms.</li>
		<li>Restate your query by using more, other or broader terms.</li>
		</ul>

			{if $parseError}
					<p class="error">{translate text='nohit_parse_error'}</p>
			{/if}
			
			{if $spellingSuggestions}
				<div class="correction">{translate text='nohit_spelling'}:<br/>
				{foreach from=$spellingSuggestions item=details key=term name=termLoop}
					{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
				{/foreach}
				</div>
				<br/>
			{/if}
			
			{if $unscopedResults > 0}
				<h2>Sample Results from entire Marmot Catalog</h2>
				<div class="unscopedResultCount">
					There are <b>{$numUnscopedResults}</b> results in the entire Marmot collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>
				</div>
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
		Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequest">Materials Request Service</a>.</div>
		{/if}
		
	</div>
</div>