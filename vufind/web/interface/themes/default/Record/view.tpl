{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetHoldingsInfo('{$id|escape:"url"}');
	{if $isbn || $upc}
		GetEnrichmentInfo('{$id|escape:"url"}', '{$isbn10|escape:"url"}', '{$upc|escape:"url"}');
	{/if}
	{if $isbn}
		GetReviewInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}');
	{/if}
	{if $enablePospectorIntegration == 1}
		GetProspectorInfo('{$id|escape:"url"}');
	{/if}
	{if $user}
		redrawSaveStatus();
	{/if}
	
	{if (isset($title)) }
		alert("{$title}");
	{/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
	getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if} 
	{include file="Record/view-sidebar.tpl"}
	
	<div id="main-content" class="full-result-content">
		<div id="record-header">
			{if isset($previousId)}
				<div id="previousRecordLink"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
			{/if}
			<div id="recordTitleAuthorGroup">
				{* Display Title *}
				<div id='recordTitle'>{$recordTitleSubtitle|regex_replace:"/(\/|:)$/":""|escape}</div>
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
			<div id ="recordTitleRight">
				{if isset($nextId)}
					<div id="nextRecordLink"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
				{/if}
				{if $lastsearch}
				<div id="returnToSearch">
					<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
				</div>
				{/if}
			</div>
		</div>
		
		<div id="image-column">
			{* Display Book Cover *}
			{if $user->disableCoverArt != 1}	
				<div id = "recordcover">	
					<div class="recordcoverWrapper">
						<img alt="{translate text='Book Cover'}" class="recordcover" src="{$bookCoverUrl}" />
						<div id="goDeeperLink" class="godeeper" style="display:none">
							<a href="{$path}/Record/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightbox('{$path}/Record/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;">
							<img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png" /></a>
						</div>
					</div>
				</div>	
			{/if}
			
			{* Place hold link *}
			<div class='requestThisLink' id="placeHold{$id|escape:"url"}" style="display:none">
				<a href="{$path}/Record/{$id|escape:"url"}/Hold" class="button">{translate text="Place Hold"}</a>
			</div>
			
			{if $goldRushLink}
			<div class ="titledetails">
				<a href='{$goldRushLink}' >Check for online articles</a>
			</div>
			{/if}
					
			{if $showRatings == 1}
				<div id="myrating" class="stat">
					<div class="statVal">
						<div class="ui-rater">
							<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:63px">&nbsp;</span></span>
						</div>
					</div>
					<script type="text/javascript">
						$(function() {literal} { {/literal}
								$('#myrating').rater({literal}{ {/literal} module:'Record', recordId: '{$shortId}', rating:'{$ratingData.average}', postHref: '{$path}/Record/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
						{literal} } {/literal});
					</script>
				</div>
			{/if}
		</div> {* End image column *}
		
		<div id="record-details-column">
			<div id="record-details-header">
				<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord"></div>
				<div id="recordTools">
					<ul>
						{if $showTextThis == 1}
							<li><a href="{$path}/Record/{$id|escape:"url"}/SMS" id="smsLink" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox", "#smsLink"); return false;'><span class="silk phone">&nbsp;</span>{translate text="Text this"}</a></li>
						{/if}
						{if $showEmailThis == 1}
							<li><a href="{$path}/Record/{$id|escape:"url"}/Email" id="mailLink" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/Email?lightbox", "#mailLink"); return false;'><span class="silk email">&nbsp;</span>{translate text="Email this"}</a></li>
						{/if}
						{if is_array($exportFormats) && count($exportFormats) > 0}
							<li>
								<a href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" onclick="toggleMenu('exportMenu'); return false;"><span class="silk application_add">&nbsp;</span>{translate text="Export Record"}</a><br />
								<ul class="menu" id="exportMenu">
									{foreach from=$exportFormats item=exportFormat}
										<li><a {if $exportFormat=="RefWorks"} {/if}href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
									{/foreach}
								</ul>
							</li>
						{/if}
						{if $showFavorites == 1}
							<li id="saveLink"><a href="{$path}/Record/{$id|escape:"url"}/Save" onclick="getSaveToListForm('{$id|escape}', 'VuFind'); return false;"><span class="silk star_gold">&nbsp;</span>{translate text="Add to favorites"}</a></li>
						{/if}
						{if $enableBookCart == 1}
							<li id="bookCartLink"><a href="#" class="cart" onclick="addToBag('{$id|escape}', '{$recordTitleSubtitle|replace:'"':''|escape:'javascript'}', this);"><span class="silk cart">&nbsp;</span>{translate text="Add to book cart"}</a></li>
						{/if}
						{if !empty($addThis)}
							<li id="addThis"><a href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}"><span class="silk tag_yellow">&nbsp;</span>{translate text='Bookmark'}</a></li>
						{/if}
					</ul>
				</div>
				<div class="clearer">&nbsp;</div>
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
							{foreach from=$subject item=subjectPart name=subloop}
								{if !$smarty.foreach.subloop.first} -- {/if}
								<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
							{/foreach}
							<br />
						{/foreach}
					</div>
				</div>
			{/if}
		</div>
	 
		{* tabs for series, similar titles, and people who viewed also viewed *}
		{if $showStrands}
			<div id="relatedTitleInfo" class="ui-tabs">
				<ul>
					<li><a href="#list-similar-titles">Similar Titles</a></li>
					<li><a href="#list-also-viewed">People who viewed this also viewed</a></li>
					<li><a id="list-series-tab" href="#list-series" style="display:none">Also in this series</a></li>
				</ul>
				
				{assign var="scrollerName" value="SimilarTitles"}
				{assign var="wrapperId" value="similar-titles"}
				{assign var="scrollerVariable" value="similarTitleScroller"}
				{include file=titleScroller.tpl}
				
				{assign var="scrollerName" value="AlsoViewed"}
				{assign var="wrapperId" value="also-viewed"}
				{assign var="scrollerVariable" value="alsoViewedScroller"}
				{include file=titleScroller.tpl}
				
			
				{assign var="scrollerName" value="Series"}
				{assign var="wrapperId" value="series"}
				{assign var="scrollerVariable" value="seriesScroller"}
				{assign var="fullListLink" value="$path/Record/$id/Series"}
				{include file=titleScroller.tpl}
				
			</div>
			{literal}
			<script type="text/javascript">
				var similarTitleScroller;
				var alsoViewedScroller;
				
				$(function() {
					$("#relatedTitleInfo").tabs();
					
					{/literal}
					
					similarTitleScroller = new TitleScroller('titleScrollerSimilarTitles', 'SimilarTitles', 'similar-titles');
					similarTitleScroller.loadTitlesFrom('{$path}/Search/AJAX?method=GetListTitles&id=strands:PROD-2&recordId={$id}&scrollerName=SimilarTitles', false);
		
					{literal}
					$('#relatedTitleInfo').bind('tabsshow', function(event, ui) {
						if (ui.index == 0) {
							similarTitleScroller.activateCurrentTitle();
						}else if (ui.index == 1) { 
							if (alsoViewedScroller == null){
								{/literal}
								alsoViewedScroller = new TitleScroller('titleScrollerAlsoViewed', 'AlsoViewed', 'also-viewed');
								alsoViewedScroller.loadTitlesFrom('{$path}/Search/AJAX?method=GetListTitles&id=strands:PROD-1&recordId={$id}&scrollerName=AlsoViewed', false);
							{literal}
							}else{
								alsoViewedScroller.activateCurrentTitle();
							}
						}
					});
				});
			</script>
			{/literal}
		{elseif $showSimilarTitles}
			<div id="relatedTitleInfo" class="ui-tabs">
				<ul>
					<li><a id="list-series-tab" href="#list-series" style="display:none">Also in this series</a></li>
					<li><a href="#list-similar-titles">Similar Titles</a></li>
				</ul>
				
				<div id="list-similar-titles" style="display:none">
					{assign var="scrollerName" value="SimilarTitlesVuFind"}
					{assign var="wrapperId" value="similar-titles-vufind"}
					{assign var="scrollerVariable" value="similarTitleVuFindScroller"}
					{include file=titleScroller.tpl}
				</div>

				<div id="list-series-tab">
					{assign var="scrollerName" value="Series"}
					{assign var="wrapperId" value="series"}
					{assign var="scrollerVariable" value="seriesScroller"}
					{assign var="fullListLink" value="$path/Record/$id/Series"}
					{include file=titleScroller.tpl}
				</div>
				
			</div>
			{literal}
			<script type="text/javascript">
				var similarTitleScroller;
				
				$(function() {
					$("#relatedTitleInfo").tabs();
					
					{/literal}
					
					similarTitleVuFindScroller = new TitleScroller('titleScrollerSimilarTitles', 'SimilarTitles', 'similar-titles');
					//similarTitleVuFindScroller.loadTitlesFrom('{$path}/Search/AJAX?method=GetListTitles&id=similarTitles&recordId={$id}&scrollerName=SimilarTitles', false);
		
					{literal}
					$('#relatedTitleInfo').bind('tabsshow', function(event, ui) {
						if (ui.index == 0) {
							similarTitleVuFindScroller.activateCurrentTitle();
						}
					});
				});
			</script>
			{/literal}
		{else}
			<div id="relatedTitleInfo" style="display:none">
				
				{assign var="scrollerName" value="Series"}
				{assign var="scrollerTitle" value="Also in this Series"}
				{assign var="wrapperId" value="series"}
				{assign var="scrollerVariable" value="seriesScroller"}
				{assign var="fullListLink" value="$path/Record/$id/Series"}
				{include file=titleScroller.tpl}
				
			</div>
			
		{/if}
		
		{include file="Record/view-tabs.tpl"}
		
	</div>
		
</div>
{if $showStrands}
{* Strands Tracking *}{literal}
<!-- Event definition to be included in the body before the Strands js library -->
<script type="text/javascript">
if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}
StrandsTrack.push({
	 event:"visited",
	 item: "{/literal}{$id|escape}{literal}"
});
</script>
{/literal}
{/if}
