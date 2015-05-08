{strip}
<div>
	<h2>{$authorName}</h2>
	<div class="row">
		<div id="wikipedia_placeholder" class="col-xs-12">
		</div>
	</div>

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
				There are <b>{$numUnscopedResults}</b> results in the entire {$consortiumName} collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>			</div>
		{/if}

		{if $spellingSuggestions}
			<br><br><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br>
			{foreach from=$spellingSuggestions item=details key=term name=termLoop}
				{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br>{/if}
			{/foreach}
		</div>
		{/if}

		{* User's viewing mode toggle switch *}
		{include file="Search/results-displayMode-toggle.tpl"}
		{*<div class="row" id="selected-browse-label">*}{* browse styling replicated here *}
			{*<div class="btn-group btn-group-sm" data-toggle="buttons">*}
				{*<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="alert(this.id)" type="radio" id="covers">*}
					{*<span class="thumbnail-icon"></span><span> Covers</span>*}
				{*</label>*}
				{*<label for="lists" title="Lists" class="btn btn-sm btn-default"><input onchange="alert(this.id);" type="radio" id="lists">*}
					{*<span class="list-icon"></span><span> Lists</span>*}
				{*</label>*}
			{*</div>*}
		{*</div>*}

		<div class="clearer"></div>
	</div>
	{* End Listing Options *}

	{include file=$resultsTemplate}

	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}

	{if $showSearchTools}
		<div class="well small">
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