{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.Record.loadHoldingsInfo('{$id|escape:"url"}', '{$id|escape:"url"}', 'eContent');
	{if $isbn || $upc}
		VuFind.Record.loadEnrichmentInfo('{$id|escape:"url"}', '{$isbn10|escape:"url"}', '{$upc|escape:"url"}', 'eContent');
	{/if}
	{if $isbn && ($showComments || $showAmazonReviews || $showStandardReviews)}
		VuFind.Record.loadReviewInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}', 'eContent');
	{/if}
	{if $enablePospectorIntegration == 1}
		VuFind.Prospector.loadRelatedProspectorTitles('{$id|escape:"url"}', 'eContent');
	{/if}
	{if $user}
		//getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
	{/if}
	{if (isset($title)) }
		//alert("{$title}");
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
		{$eContentRecord->title|removeTrailingPunctuation|escape}{if $eContentRecord->subTitle}: {$eContentRecord->subTitle|removeTrailingPunctuation|escape}{/if}
		{if $eContentRecord->format()}
			&nbsp;<small>({implode subject=$eContentRecord->format() glue=", "})</small>
		{/if}
	</h2>
{* Display more information about the title*}
	{if $eContentRecord->author}
		<h3>
			by <a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a>
		</h3>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	{if $user && $user->hasRole('epubAdmin')}
		<span id="eContentStatus">{$eContentRecord->status} </span>
		<div class="btn-group">
			<a href='{$path}/EcontentRecord/{$id}/Edit' class="btn btn-sm">edit</a>
			{if $eContentRecord->status != 'archived' && $eContentRecord->status != 'deleted'}
				<a href='{$path}/EcontentRecord/{$id}/Archive' onclick="return confirm('Are you sure you want to archive this record?	The record should not have any holds or checkouts when it is archived.')" class="btn btn-sm">archive</a>
			{/if}
			{if $eContentRecord->status != 'deleted'}
				<a href='{$path}/EcontentRecord/{$id}/Delete' onclick="return confirm('Are you sure you want to delete this record?	The record should not have any holds or checkouts when it is deleted.')" class="btn btn-sm">delete</a>
			{/if}
		</div>
	{/if}

	<hr/>

	<div id="main-content" class="col-md-12">
		<div class="row">
			<div id="image-column" class="col-md-3">
				{* Display Book Cover *}
				{if $user->disableCoverArt != 1}
					<div id = "recordcover">
						<img alt="{translate text='Book Cover'}" class="img-polaroid" src="{$bookCoverUrl}" />
					</div>
				{/if}

				{if $goldRushLink}
					<div class ="titledetails">
						<a href='{$goldRushLink}' >Check for online articles</a>
					</div>
				{/if}
			</div> {* End image column *}

			<div id="record-details-column" class="col-md-6">
				<div id="record-details-header">
					<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading...</div>
				</div>

				{if $cleanDescription}
					<dl>
						<dt>{translate text='Description'}</dt>
						<dd class="recordDescription">
							{$cleanDescription}
						</dd>
					</dl>
				{/if}
			</div>

			<div id="recordTools" class="col-md-3">
				{include file="EcontentRecord/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}

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

		{include file="EcontentRecord/view-tabs.tpl" isbn=$isbn upc=$upc}

		<hr/>
	</div>
{/strip}