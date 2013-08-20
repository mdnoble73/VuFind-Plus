{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	VuFind.Record.loadHoldingsInfo('{$id|escape:"url"}', 'VuFind');
	{if $isbn || $upc}
		VuFind.Record.loadEnrichmentInfo('{$id|escape:"url"}', '{$isbn10|escape:"url"}', '{$upc|escape:"url"}');
	{/if}
	{if $isbn}
		VuFind.Record.loadReviewInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}');
	{/if}
	{if $enablePospectorIntegration == 1}
		VuFind.Prospector.loadRelatedProspectorTitles('{$id|escape:"url"}');
	{/if}
	{if $user}
		redrawSaveStatus();
	{/if}
	
	{if (isset($title)) }
		alert("{$title}");
	{/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
	//getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

<div class="nav navbar">
	{if isset($previousId)}
		<div id="previousRecordLink" class="pull-left"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
	{/if}
	{if isset($nextId)}
		<div id="nextRecordLink"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
	{/if}
	{if $lastsearch}
		<div id="returnToSearch">
			<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
		</div>
	{/if}
</div>

{* Display Title *}
<h3>{$recordTitleSubtitle|removeTrailingPunctuation|escape}</h3>
<div  class="row-fluid">
	{* Display more information about the title*}
	{if $mainAuthor}
		<div class="recordAuthor">
			<span class="resultLabel">by</span>&nbsp;
			<span class="resultValue"><a href="{$path}/Author/Home?author={$mainAuthor|escape:"url"}">{$mainAuthor|escape}</a></span>
		</div>
	{/if}

	{if $corporateAuthor}
		<div class="recordAuthor">
			<span class="resultLabel">{translate text='Corporate Author'}:</span>
			<span class="resultValue"><a href="{$path}/Author/Home?author={$corporateAuthor|escape:"url"}">{$corporateAuthor|escape}</a></span>
		</div>
	{/if}
</div>

{if $error}<p class="error">{$error}</p>{/if}

<div class="row-fluid">


	{*
	<div class="span3">
		{include file="Record/view-sidebar.tpl"}
	</div>
	*}
	
	<div id="main-content" class="span12">
		<div class="row-fluid">
			<div id="image-column" class="span3">
				{* Display Book Cover *}
				{if $user->disableCoverArt != 1}
					<div id = "recordcover">
						<img alt="{translate text='Book Cover'}" class="img-polaroid" src="{$bookCoverUrl}" />
						<div id="goDeeperLink" class="godeeper" style="display:none">
							<a href="{$path}/Record/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightbox('{$path}/Record/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;">
							<img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png" /></a>
						</div>
					</div>
				{/if}

				{if $goldRushLink}
				<div class ="titledetails">
					<a href='{$goldRushLink}' >Check for online articles</a>
				</div>
				{/if}
			</div> {* End image column *}
		
			<div id="record-details-column" class="span6">
				<div id="record-details-header">
					<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord"></div>
				</div>

				{if $summary}
					<div class="resultInformation">
						<div class="resultInformationLabel">{translate text='Description'}</div>
						<div class="recordDescription">
							{if strlen($summary) > 300}
								<span id="shortSummary">
								{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'|truncate:300}{*Leave unescaped because some syndetics reviews have html in them *}
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

				{if $subjects}
					<div class="resultInformation">
						<div class="resultInformationLabel">{translate text='Subjects'}</div>
						<div class="recordSubjects">
							{foreach from=$subjects item=subject name=loop}
								{if $smarty.foreach.loop.index == 5}
									<div id="subjectsMoreLink"><a href="#" onclick="$('#subjectsMoreSection').toggle();$('#subjectsMoreLink').toggle();">{translate text="more"}...</a></div>
									<div id="subjectsMoreSection" style="display:none">
								{/if}
								{foreach from=$subject item=subjectPart name=subloop}
									{if !$smarty.foreach.subloop.first} -- {/if}
									<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
								{/foreach}
								<br />
							{/foreach}
							{if $smarty.foreach.loop.index >= 5}
								<div id="subjectsLessLink"><a href="#" onclick="$('#subjectsMoreSection').toggle();$('#subjectsMoreLink').toggle();">{translate text="less"}</a></div>
								</div>
							{/if}
						</div>
					</div>
				{/if}
			</div>

			<div id="recordTools" class="span3">
				{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}

				<div id="ratings" class="well center">
					<h4>Rating:</h4>
					{* Let the user rate this title *}
					{include file="Record/title-rating.tpl" ratingClass="" recordId=$id shortId=$shortId ratingData=$ratingData showFavorites=0}
				</div>

				{include file="Record/view-tags.tpl"}
			</div>

		</div>
	 
		<div id="relatedTitleInfo" style="display:none" class="row-fluid">
			{assign var="scrollerName" value="Series"}
			{assign var="scrollerTitle" value="Also in this Series"}
			{assign var="wrapperId" value="series"}
			{assign var="scrollerVariable" value="seriesScroller"}
			{assign var="fullListLink" value="$path/Record/$id/Series"}
			{include file='titleScroller.tpl'}
		</div>

		{include file="Record/view-tabs.tpl" isbn=$isbn upc=$upc}
	</div>
</div>
{literal}
<script type="text/javascript">
	$(document).ready(function(){
		VuFind.ResultsList.loadStatusSummaries();
	});
</script>
{/literal}