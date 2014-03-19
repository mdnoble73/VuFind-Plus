{strip}
<div>
	<h2>{$authorName}</h2>
	<div id="wikipedia_placeholder" class="row"></div>

	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">

		{if $recordCount}
			{translate text="Showing"} {$recordStart} - {$recordEnd} {translate text='of'} {$recordCount|number_format}
		{/if}
		<span class="hidden-phone">
			 &nbsp;{translate text='query time'}: {$qtime}s
		</span>
		{if $replacementTerm}
			<div id="replacement-search-info">
				<span class="replacement-search-info-text">Showing Results for </span>{$replacementTerm}<span class="replacement-search-info-text">.  Search instead for <span class="replacement-search-info-text"><a href="{$oldSearchUrl}">{$oldTerm}</a>
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

	{include file='Search/list-list.tpl'}

	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}

	{if $showSearchTools}
		<div class="well well-small">
			<strong>{translate text='Search Tools'}:</strong>
			<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return VuFind.Account.ajaxLightbox('{$path}/Search/AJAX?method=getEmailForm', true);"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
		</div>
	{/if}
</div>
{/strip}
{if $showWikipedia}
	{literal}
	<script type="text/javascript">
		$(document).ready(function (){
			VuFind.Wikipedia.getWikipediaArticle('{/literal}{$wikipediaAuthorName}{literal}');
		});
	</script>
	{/literal}
{/if}