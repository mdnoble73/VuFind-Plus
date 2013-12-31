<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	//VuFind.Record.loadHoldingsInfo('{$id|escape:"url"}', '{$id|escape:"url"}', 'eContent');
	{if $isbn || $upc}
		//VuFind.Record.loadEnrichmentInfo('{$id|escape:"url"}', '{$isbn10|escape:"url"}', '{$upc|escape:"url"}', 'eContent');
	{/if}
	{if $isbn && ($showComments || $showAmazonReviews || $showStandardReviews)}
		//VuFind.Record.loadReviewInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}', 'eContent');
	{/if}
	{if $enablePospectorIntegration == 1}
		//VuFind.Prospector.loadRelatedProspectorTitles('{$id|escape:"url"}', 'eContent');
	{/if}
{literal}});{/literal}
</script>
{strip}
	<div class="btn-group">
		{if isset($previousId)}
			<div id="previousRecordLink" class="btn"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
		{/if}
		{if $lastsearch}
			<div id="returnToSearch" class="btn">
				<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Search Results"}</a>
			</div>
		{/if}
		{if isset($nextId)}
			<div id="nextRecordLink" class="btn"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
		{/if}
	</div>

{* Display Title *}
	<h2>
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubTitle()}: {$recordDriver->getSubTitle()|removeTrailingPunctuation|escape}{/if}
		{if $recordDriver->getFormats()}
			&nbsp;<small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
		{/if}
	</h2>
{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<h3>
			by <a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|escape}</a>
		</h3>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	<hr/>

	<div id="main-content" class="span12">
		<div class="row-fluid">
			<div id="image-column" class="span3">
				{* Display Book Cover *}
				{if $user->disableCoverArt != 1}
					<div id = "recordcover">
						<img alt="{translate text='Book Cover'}" class="img-polaroid" src="{$recordDriver->getCoverUrl('large')}" />
					</div>
				{/if}
			</div> {* End image column *}

			<div id="record-details-column" class="span6">
				<div id="record-details-header">
					<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading...</div>
				</div>

				{if $recordDriver->getDescription()}
					<dl>
						<dt>{translate text='Description'}</dt>
						<dd class="recordDescription">
							{$recordDriver->getDescription()}
						</dd>
					</dl>
				{/if}
			</div>

			<div id="recordTools" class="span3">
				{include file="OverDrive/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}

				<div id="ratings" class="well center">
					{* Let the user rate this title *}
					{include file="Record/title-rating-full.tpl" ratingClass="" recordId=$id shortId=$shortId ratingData=$ratingData showFavorites=0}
				</div>
			</div>
		</div>

		<div id="relatedTitleInfo" style="display:none">

			{assign var="scrollerName" value="Series"}
			{assign var="scrollerTitle" value="Also in this Series"}
			{assign var="wrapperId" value="series"}
			{assign var="scrollerVariable" value="seriesScroller"}
			{assign var="fullListLink" value="$path/EcontentRecord/$id/Series"}
			{include file="titleScroller.tpl"}
		</div>

		<hr/>

		{include file="OverDrive/view-tabs.tpl" isbn=$isbn upc=$upc}

		<hr/>
	</div>
{/strip}