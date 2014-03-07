<script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
{strip}
{* Display Title *}
	<h2 class="notranslate">
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}
	</h2>

	{if $recordDriver->getPrimaryAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value notranslate">
				<a href="{$path}/Author/Home?author={$recordDriver->getPrimaryAuthor()|escape:"url"}">{$recordDriver->getPrimaryAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	{if $recordDriver->getSeries()}
		<div class="series{$summISBN} row">
			<div class="result-label col-md-3">Series: </div>
			<div class="col-md-9 result-value">
				{assign var=summSeries value=$recordDriver->getSeries()}
				<a href="{$path}/GroupedWork/{$recordDriver->getId()}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
			</div>
		</div>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}


	{if $recordDriver->getDescription()}
		<div class="row">
			<div class="col-sm-12">
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
			</div>
		</div>
	{/if}


	{assign value=$recordDriver->getRelatedManifestations() var="relatedManifestations"}
	{include file="GroupedWork/relatedManifestations.tpl"}

	{include file=$moreDetailsTemplate}
{/strip}
<script type="text/javascript">
	{literal}$(document).ready(function(){{/literal}
		VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
		VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	{literal}});{/literal}
</script>