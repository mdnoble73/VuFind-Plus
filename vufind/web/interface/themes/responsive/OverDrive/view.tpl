<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.Record.loadHoldingsInfo('{$id|escape:"url"}', '{$id|escape:"url"}', 'OverDrive');
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
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	<div id="main-content" class="col-md-12">
		<div class="row">
			<div id="record-details-column" class="col-md-9">
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

			<div id="recordTools" class="col-md-3">
				{include file="OverDrive/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}

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

		{* include file="OverDrive/view-tabs.tpl" isbn=$isbn upc=$upc *}

		<hr/>
	</div>
{/strip}