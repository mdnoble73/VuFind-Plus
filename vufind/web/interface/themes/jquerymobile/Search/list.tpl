{strip}
<div data-role="page" id="Search-list" class="results-page">
	{include file="header.tpl"}
	<div data-role="content">
		{include file="Search/Recommend/TopFacets.tpl"}
		
		{if $recordCount}
			<p>
				<strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong> {translate text='of'} <strong>{$recordCount}</strong>
				&nbsp;{if $searchType == 'basic'}{translate text='for'}: <strong>{$lookfor|escape:"html"}</strong>{/if}
			</p>
		{/if}
		{if $numUnscopedResults && $numUnscopedResults != $recordCount}
			<p class="unscopedResultCount">
				There are <b>{$numUnscopedResults}</b> results in the entire Marmot collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>
			</p>
		{/if}
		{if $subpage}
			{include file=$subpage}
		{else}
			{$pageContent}
		{/if}
	</div>
	{include file="footer.tpl"}
</div>

{include file="Search/Recommend/SideFacets.tpl"}
{/strip}