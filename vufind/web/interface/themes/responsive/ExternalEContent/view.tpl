{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript" src="{$path}/interface/themes/responsive/js/vufind/title-scroller.js"></script>
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.ExternalEContentRecord.loadHoldingsInfo('{$id|escape:"url"}', '{$shortId}', 'VuFind');
	VuFind.GroupedWork.loadEnrichmentInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	VuFind.GroupedWork.loadReviewInfo('{$recordDriver->getPermanentId()|escape:"url"}');
	{if $enablePospectorIntegration == 1}
		VuFind.Prospector.loadRelatedProspectorTitles('{$id|escape:"url"}', 'VuFind');
	{/if}
{literal}});{/literal}
</script>
{strip}

	{* Display Title *}
	<h2>
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubTitle()}: {$recordDriver->getSubTitle()|removeTrailingPunctuation|escape}{/if}
		{if $recordDriver->getFormats()}
			&nbsp;<small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
		{/if}
	</h2>
	{* Display more information about the title*}
	{if $mainAuthor}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href="{$path}/Author/Home?author={$mainAuthor|escape:"url"}">{$mainAuthor|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	{if $corporateAuthor}
		<h3>
			by <a href="{$path}/Author/Home?author={$corporateAuthor|escape:"url"}">{$corporateAuthor|escape}</a>
		</h3>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	<div class="row">

		{*
		<div class="col-md-3">
			{include file="Record/view-sidebar.tpl"}
		</div>
		*}

		<div id="main-content" class="col-sm-12">
			<div class="row">

				<div id="record-details-column" class="col-sm-9">
					<div id="record-details-header">
						<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading availability information...</div>
					</div>

					{if $summary}
						<dl>
							<dt>{translate text='Description'}</dt>
							<dd>
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
							</dd>
						</dl>
					{/if}
				</div>

				<div id="recordTools" class="col-md-3">
					{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}
				</div>
			</div>

			<div id="seriesInfo" style="display:none" class="row">
				<div class="col-sm-12">
					{assign var="scrollerName" value="Series"}
					{assign var="scrollerTitle" value="Also in this Series"}
					{assign var="wrapperId" value="series"}
					{assign var="scrollerVariable" value="seriesScroller"}
					{assign var="permanentId" value=$recordDriver->getPermanentId()}
					{assign var="fullListLink" value= "$path/GroupedWork/$permanentId/Series"}
					{include file='titleScroller.tpl'}
				</div>
			</div>

			<div id="moreLikeThisInfo" style="display:none" class="row">
				<div class="col-sm-12">
					{assign var="scrollerName" value="MoreLikeThis"}
					{assign var="scrollerTitle" value="More Like This"}
					{assign var="wrapperId" value="morelikethis"}
					{assign var="scrollerVariable" value="morelikethisScroller"}
					{assign var="permanentId" value=$recordDriver->getPermanentId()}
					{include file='titleScroller.tpl'}
				</div>
			</div>

			{include file=$moreDetailsTemplate}
		</div>
	</div>
	{literal}
	<script type="text/javascript">
		$(document).ready(function(){
			VuFind.ResultsList.loadStatusSummaries();
		});
	</script>
	{/literal}
{/strip}