<script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
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
		{$recordDriver->getTitle()|removeTrailingPunctuation|escape}
	</h2>
	{if $recordDriver->getPrimaryAuthor()}
		<h3>by <a href="{$path}/Author/Home?author={$recordDriver->getPrimaryAuthor()|escape:"url"}">{$recordDriver->getPrimaryAuthor()|escape}</a></h3>
	{/if}

	{if $error}<p class="error">{$error}</p>{/if}

	<div class="row-fluid">
		<div id="main-content" class="span12">
			<div class="row-fluid">
				<div id="image-column" class="span3">
					{* Display Book Cover *}
					{if $user->disableCoverArt != 1}
						<div id = "recordcover">
							<img alt="{translate text='Book Cover'}" class="img-polaroid" src="{$recordDriver->getBookcoverUrl('large')}" />
						</div>
					{/if}
				</div> {* End image column *}

				<div id="record-details-column" class="span6">
					{if $recordDriver->getDescription()}
						{assign value=$recordDriver->getDescription() var="summary"}
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

					{assign value=$recordDriver->getRelatedManifestations() var="relatedManifestations"}
					{include file="GroupedWork/relatedManifestations.tpl"}
				</div>

				<div id="recordTools" class="span3">
					{include file="GroupedWork/result-tools.tpl" showMoreInfo=false}

					<div id="ratings" class="well center">
						{* Let the user rate this title *}
						{include file="GroupedWork/title-rating-full.tpl" ratingClass="" showFavorites=0}
					</div>
				</div>
			</div>

			<div id="relatedTitleInfo" style="display:none" class="row-fluid">
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