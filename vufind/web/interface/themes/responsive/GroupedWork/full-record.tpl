<script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
{strip}
{* Display Title *}
	<h2>
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}
	</h2>

	{if $recordDriver->getAuthors()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				{foreach from=$recordDriver->getAuthors() item=author}
					<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getSeries()}
		<div class="series{$summISBN} row">
			<div class="result-label col-md-3">Series: </div>
			<div class="col-md-9 result-value">
				{assign var=summSeries value=$recordDriver->getSeries()}
				<a href="{$path}/Search/Results?lookfor={$summSeries.seriesTitle|urlencode}">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
			</div>
		</div>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	<div class="row">
		<div id="main-content" class="col-sm-12">
			<div class="row">
				{if $recordDriver->getDescription()}
					<span class="result-label">Description: </span>
					{assign value=$recordDriver->getDescription() var="summary"}
					{if strlen($summary) > 600}
						<span id="shortSummary">
						{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'|truncate:600}{*Leave unescaped because some syndetics reviews have html in them *}
							<a href='#' onclick='$("#shortSummary").slideUp();$("#fullSummary").slideDown()'>More</a>
						</span>
						<span id="fullSummary" style="display:none">
						{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
							<a href='#' onclick='$("#shortSummary").slideDown();$("#fullSummary").slideUp()'>Less</a>
						</span>
					{else}
						{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
					{/if}
				{/if}

				{assign value=$recordDriver->getRelatedManifestations() var="relatedManifestations"}
				{include file="GroupedWork/relatedManifestations.tpl"}
			</div>

			<div id="relatedTitleInfo" style="display:none" class="row">
				{assign var="scrollerName" value="Series"}
				{assign var="scrollerTitle" value="Also in this Series"}
				{assign var="wrapperId" value="series"}
				{assign var="scrollerVariable" value="seriesScroller"}
				{assign var="permanentId" value=$recordDriver->getPermanentId()}
				{assign var="fullListLink" value= "$path/GroupedWork/$permanentId/Series"}
				{include file='titleScroller.tpl'}
			</div>

			<hr/>

			{include file="GroupedWork/view-tabs.tpl"}
		</div>
	</div>
{/strip}
<script type="text/javascript">
	{literal}$(document).ready(function(){{/literal}
		VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	{literal}});{/literal}
</script>