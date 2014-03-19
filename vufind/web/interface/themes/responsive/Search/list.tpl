<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">

		{if $recordCount}
			{translate text="Showing"}
			{$recordStart} - {$recordEnd}
			{translate text='of'} {$recordCount|number_format}
		{/if}
		<span class="hidden-phone">
			 {translate text='query time'}: {$qtime}s
		</span>
		{if $replacementTerm}
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">Showing Results for</span> {$replacementTerm}</div>
				<div id="original-search-info"><span class="replacement-search-info-text">Search instead for </span><a href="{$oldSearchUrl}">{$oldTerm}</a></div>
			</div>
		{/if}

		{if $numUnscopedResults && $numUnscopedResults != $recordCount}
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

	{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}

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

	{if $showSearchTools || ($user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor')))}
	<div class="searchtools well">
		<strong>{translate text='Search Tools'}:</strong>
		{if $showSearchTools}
			<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return VuFind.Account.ajaxLightbox('{$path}/Search/AJAX?method=getEmailForm', true);"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
			{if $savedSearch}<a href="{$path}/MyResearch/SaveSearch?delete={$searchId}"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>{else}<a href="{$path}/MyResearch/SaveSearch?save={$searchId}"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>{/if}
			<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
		{/if}
		{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
			<a href="#" onclick="return VuFind.ListWidgets.createWidgetFromSearch('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Create Widget'}</a>
		{/if}
	</div>
	{/if}
</div>
