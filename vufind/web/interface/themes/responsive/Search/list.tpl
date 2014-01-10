{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div class="row">
	{* Narrow Search Options *}
	<div id="sidebar" class="col-md-3">
		<div class="sidegroup well">
			{if $recordCount}
				<label for="sort"><strong>{translate text='Sort By'}</strong></label>

				<select id="sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
					{foreach from=$sortList item=sortData key=sortLabel}
						<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
					{/foreach}
				</select>
			{/if}
		</div>

		{if $sideRecommendations}
			{foreach from=$sideRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		{/if}
	</div>
	{* End Narrow Search Options *}

	<div id="main-content" class="col-md-9">
		<div id="searchInfo">
			{* Recommendations *}
			{if $topRecommendations}
				{foreach from=$topRecommendations item="recommendations"}
					{include file=$recommendations}
				{/foreach}
			{/if}

			{* Listing Options *}
			<div class="resulthead">
				{if $replacementTerm}
					<div id="replacementSearchInfo">
						<div style="font-size:120%">Showing Results for: <strong><em>{$replacementTerm}</em></strong></div> 
						<div style="font-size:95%">Search instead for: <a href="{$oldSearchUrl}">{$oldTerm}</a></div>
					</div>
				{/if}
				{if $recordCount}
					{translate text="Showing"}
					<b>{$recordStart}</b> - <b>{$recordEnd}</b>
					{translate text='of'} <b>{$recordCount}</b>
					{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>{/if}
				{/if}
				<span class="hidden-phone">
					,&nbsp;{translate text='query time'}: {$qtime}s
				</span>

				{if $numUnscopedResults && $numUnscopedResults != $recordCount}
					<br />
					<div class="unscopedResultCount">
						There are <b>{$numUnscopedResults}</b> results in the entire Marmot collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>
					</div>
				{/if}
				
				{if $spellingSuggestions}
					<br /><br /><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br/>
					{foreach from=$spellingSuggestions item=details key=term name=termLoop}
						{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
					{/foreach}
					</div>
				{/if}

				<div class="clearer"></div>
			</div>
			{* End Listing Options *}

			{if $subpage}
				{include file=$subpage}
			{else}
				{$pageContent}
			{/if}

			{if $pageLinks.all}<div class="pagination pagination-centered">{$pageLinks.all}</div>{/if}
			
			{if $unscopedResults > 0}
				<h2>More results from the Marmot Catalog</h2>
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
				<script type="text/javascript">VuFind.Prospector.getProspectorResults({$prospectorNumTitlesToLoad}, {$prospectorSavedSearchId});</script>
				{* Prospector Results *}
				<div id='prospectorSearchResultsPlaceholder'></div>
			{/if}

			{if $enableMaterialsRequest}
				<h2>Didn't find it?</h2>
				<p>Can't find what you are looking for? <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$lookfor}&basicType={$searchIndex}">Suggest a purchase</a>.</p>
			{/if}

			<div class="searchtools well">
				<strong>{translate text='Search Tools'}:</strong>
				<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
				<a href="{$path}/Search/Email" onclick="ajaxLightbox('/Search/Email?lightbox'); return false;"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
				{if $savedSearch}<a href="{$path}/MyResearch/SaveSearch?delete={$searchId}"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>{else}<a href="{$path}/MyResearch/SaveSearch?save={$searchId}"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>{/if}
				<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
				{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
					<a href="#" onclick="return createWidgetFromSearch('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Create Widget'}</a>
				{/if}
			</div>
		</div>
		{* End Main Listing *}
	</div>
</div>
