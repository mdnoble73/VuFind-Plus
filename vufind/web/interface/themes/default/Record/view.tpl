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
	<div id="sidebar">
		<div class="sidegroup" id="titleDetailsSidegroup">
			<h4>{translate text="Title Details"}</h4>
			{if $mainAuthor}
					<div class="sidebarLabel">{translate text='Main Author'}:</div>
					<div class="sidebarValue"><a href="{$path}/Author/Home?author={$mainAuthor|trim|escape:"url"}">{$mainAuthor|escape}</a></div>
					{/if}
					
					{if $corporateAuthor}
					<div class="sidebarLabel">{translate text='Corporate Author'}:</div>
					<div class="sidebarValue"><a href="{$path}/Author/Home?author={$corporateAuthor|trim|escape:"url"}">{$corporateAuthor|escape}</a>a></div>
					{/if}
					
					{if $contributors}
					<div class="sidebarLabel">{translate text='Contributors'}:</div>
					{foreach from=$contributors item=contributor name=loop}
						<div class="sidebarValue"><a href="{$path}/Author/Home?author={$contributor|trim|escape:"url"}">{$contributor|escape}</a></div>
					{/foreach}
					{/if}
					
					{if $published}
					<div class="sidebarLabel">{translate text='Published'}:</div>
					{foreach from=$published item=publish name=loop}
						<div class="sidebarValue">{$publish|escape}</div>
					{/foreach}
					{/if}
					
					{if $streetDate}
						<div class="sidebarLabel">{translate text='Street Date'}:</div>
						<div class="sidebarValue">{$streetDate|escape}</div>
					{/if}
					
					<div class="sidebarLabel">{translate text='Format'}:</div>
					{if is_array($recordFormat)}
					 {foreach from=$recordFormat item=displayFormat name=loop}
						 <div class="sidebarValue"><span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span></div>
					 {/foreach}
					{else}
						<div class="sidebarValue"><span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span></div>
					{/if}
					
					{if $mpaaRating}
						<div class="sidebarLabel">{translate text='Rating'}:</div>
						<div class="sidebarValue">{$mpaaRating|escape}</div>
					{/if}
					
					{if $physicalDescriptions}
			<div class="sidebarLabel">{translate text='Physical Desc'}:</div>
				{foreach from=$physicalDescriptions item=physicalDescription name=loop}
						<div class="sidebarValue">{$physicalDescription|escape}</div>
					{/foreach}
			{/if}
					
					<div class="sidebarLabel">{translate text='Language'}:</div>
					{foreach from=$recordLanguage item=lang}
						<div class="sidebarValue">{$lang|escape}</div>
					{/foreach}
					
					{if $editionsThis}
					<div class="sidebarLabel">{translate text='Edition'}:</div>
					{foreach from=$editionsThis item=edition name=loop}
						<div class="sidebarValue">{$edition|escape}</div>
					{/foreach}
					{/if}
					
					{if $isbns}
					<div class="sidebarLabel">{translate text='ISBN'}:</div>
					{foreach from=$isbns item=tmpIsbn name=loop}
						<div class="sidebarValue">{$tmpIsbn|escape}</div>
					{/foreach}
					{/if}
					
					{if $issn}
					<div class="sidebarLabel">{translate text='ISSN'}:</div>
						<div class="sidebarValue">{$issn}</div>
						{if $goldRushLink}
				<div class="sidebarValue"><a href='{$goldRushLink}' target='_blank'>Check for online articles</a></div>
			{/if}
					{/if}
					
					{if $upc}
					<div class="sidebarLabel">{translate text='UPC'}:</div>
					<div class="sidebarValue">{$upc|escape}</div>
					{/if}
					
					{if $series}
					<div class="sidebarLabel">{translate text='Series'}:</div>
					{foreach from=$series item=seriesItem name=loop}
						<div class="sidebarValue"><a href="{$path}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a></div>
					{/foreach}
					{/if}
					
					{if $arData}
						<div class="sidebarLabel">{translate text='Accelerated Reader'}:</div>
						<div class="sidebarValue">{$arData.interestLevel|escape}</div>
						<div class="sidebarValue">Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points</div>
					{/if}
					
					{if $lexileScore}
						<div class="sidebarLabel">{translate text='Lexile Score'}:</div>
						<div class="sidebarValue">{$lexileScore|escape}</div>
					{/if}
					
		</div>
		
		{if $showTagging == 1}
		<div class="sidegroup" id="tagsSidegroup">
			<h4>{translate text="Tags"}</h4>
			<div id="tagList">
			{if $tagList}
				{foreach from=$tagList item=tag name=tagLoop}
					<div class="sidebarValue"><a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})</div>
				{/foreach}
			{else}
				<div class="sidebarValue">{translate text='No Tags'}, {translate text='Be the first to tag this record'}!</div>
			{/if}
			</div>
			<div class="sidebarValue">
				<a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=VuFind" class="tool add"
					 onclick="GetAddTagForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add Tag"}</a>
			</div>
		</div>
		{/if}
		
		<div class="sidegroup" id="similarTitlesSidegroup">
		 {* Display either similar tiles from novelist or from the catalog*}
		 <div id="similarTitlePlaceholder"></div>
		 {if is_array($similarRecords)}
		 <div id="relatedTitles">
			<h4>{translate text="Other Titles"}</h4>
			<ul class="similar">
				{foreach from=$similarRecords item=similar}
				<li>
					{if is_array($similar.format)}
						<span class="{$similar.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
					{else}
						<span class="{$similar.format|lower|regex_replace:"/[^a-z0-9]/":""}">
					{/if}
					<a href="{$path}/Record/{$similar.id|escape:"url"}">{$similar.title|regex_replace:"/(\/|:)$/":""|escape}</a>
					</span>
					<span style="font-size: 80%">
					{if $similar.author}<br/>{translate text='By'}: {$similar.author|escape}{/if}
					</span>
				</li>
				{/foreach}
			</ul>
		 </div>
		 {/if}
		</div>
		
		<div class="sidegroup" id="similarAuthorsSidegroup">
			<div id="similarAuthorPlaceholder"></div>
		</div>
		
		{if is_array($editions) && !$showOtherEditionsPopup}
		<div class="sidegroup" id="otherEditionsSidegroup">
			<h4>{translate text="Other Editions"}</h4>
				{foreach from=$editions item=edition}
					<div class="sidebarLabel">
						<a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
					</div>
					<div class="sidebarValue">
					{if is_array($edition.format)}
						{foreach from=$edition.format item=format}
							<span class="{$format|lower|regex_replace:"/[^a-z0-9]/":""}">{$format}</span>
						{/foreach}
					{else}
						<span class="{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">{$edition.format}</span>
					{/if}
					{$edition.edition|escape}
					{if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
					</div>
				{/foreach}
		</div>
		{/if}
		
		{if $enablePospectorIntegration == 1}
		<div class="sidegroup">
		{* Display in Prospector Sidebar *}
		<div id="inProspectorPlaceholder"></div>
		</div>
		{/if}
		
		{if $linkToAmazon == 1 && $isbn}
		<div class="titledetails">
			<a href="http://amazon.com/dp/{$isbn|@formatISBN}" class='amazonLink'> {translate text = "View on Amazon"}</a>
		</div>
		{/if}
		
		{if $classicId}
		<div id = "classicViewLink"><a href ="{$classicUrl}/record={$classicId|escape:"url"}" target="_blank">Classic View</a></div>
		{/if}
	</div> {* End sidebar *}
	
	<div id="main-content" class="full-result-content">
		<div id="record-header">
			{if isset($previousId)}
				<div id="previousRecordLink"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
			{/if}
			<div id="recordTitleAuthorGroup">
				{* Display Title *}
				<div id='recordTitle'>{$recordTitleSubtitle|regex_replace:"/(\/|:)$/":""|escape}</div>
				{* Display more information about the title*}
				{if $mainAuthor}
					<div class="recordAuthor">
						<span class="resultLabel">by</span>
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
					<div id="nextRecordLink"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
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
					
					<a href="{$bookCoverUrl}">							
						<img alt="{translate text='Book Cover'}" class="recordcover" src="{$bookCoverUrl}" />
					</a>
					<div id="goDeeperLink" class="godeeper" style="display:none">
						<a href="{$path}/Record/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightbox('{$path}/Record/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;">
						<img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png" /></a>
					</div>
				</div>
			</div>	
			{/if}
			
			{* Place hold link *}
		<div class='requestThisLink' id="placeHold{$id|escape:"url"}" style="display:none">
			<a href="{$path}/Record/{$id|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
		</div>
		{if $showOtherEditionsPopup}
		<div id="otherEditionCopies">
			<div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummaries('{$id}', false)">{translate text="Other Formats and Languages"}</a></div>
		</div>
		{/if}
		
			{if $goldRushLink}
			<div class ="titledetails">
				<a href='{$goldRushLink}' >Check for online articles</a>
			</div>
			{/if}
					
				
			<div id="myrating" class="stat">
			<div class="statVal">
			<div class="ui-rater">
				<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:63px"></span></span>
				</div>
				</div>
				<script type="text/javascript">
				$(
				 function() {literal} { {/literal}
						 $('#myrating').rater({literal}{ {/literal} module:'Record', recordId: '{$shortId}', rating:'{$ratingData.average}', postHref: '{$path}/Record/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
				 {literal} } {/literal}
			);
				</script>
			</div>
		</div> {* End image column *}
		
		<div id="record-details-column">
			<div id="record-details-header">
				<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord"></div>
				
				<div id="recordTools">
				<ul>
					
					{if !$tabbedDetails}
						<li><a href="{$path}/Record/{$id|escape:"url"}/Cite" class="cite" id="citeLink" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/Cite?lightbox", "#citeLink"); return false;'>{translate text="Cite this"}</a></li>
					{/if}
					{if $showTextThis == 1}
						<li><a href="{$path}/Record/{$id|escape:"url"}/SMS" class="sms" id="smsLink" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox", "#smsLink"); return false;'>{translate text="Text this"}</a></li>
					{/if}
					{if $showEmailThis == 1}
						<li><a href="{$path}/Record/{$id|escape:"url"}/Email" class="mail" id="mailLink" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/Email?lightbox", "#mailLink"); return false;'>{translate text="Email this"}</a></li>
					{/if}
					{if is_array($exportFormats) && count($exportFormats) > 0}
						<li>
							<a href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onclick="toggleMenu('exportMenu'); return false;">{translate text="Export Record"}</a><br />
							<ul class="menu" id="exportMenu">
								{foreach from=$exportFormats item=exportFormat}
									<li><a {if $exportFormat=="RefWorks"} {/if}href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
								{/foreach}
							</ul>
						</li>
					{/if}
					{if $showFavorites == 1}
						<li id="saveLink"><a href="{$path}/Record/{$id|escape:"url"}/Save" class="fav" onclick="getSaveToListForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add to favorites"}</a></li>
					{/if}
					{if !empty($addThis)}
						<li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></li>
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
					$("#moredetails-tabs").tabs();
					
					{/literal}
					{if $defaultDetailsTab}
						$("#moredetails-tabs").tabs('select', '{$defaultDetailsTab}');
					{/if}
					
					similarTitleScroller = new TitleScroller('titleScrollerSimilarTitles', 'SimilarTitles', 'similar-titles');
					similarTitleScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=strands:PROD-2&recordId={$id}&scrollerName=SimilarTitles', false);
		
					{literal}
					$('#relatedTitleInfo').bind('tabsshow', function(event, ui) {
						if (ui.index == 0) {
							similarTitleScroller.activateCurrentTitle();
						}else if (ui.index == 1) { 
							if (alsoViewedScroller == null){
								{/literal}
								alsoViewedScroller = new TitleScroller('titleScrollerAlsoViewed', 'AlsoViewed', 'also-viewed');
								alsoViewedScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=strands:PROD-1&recordId={$id}&scrollerName=AlsoViewed', false);
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
					<li><a href="#list-similar-titles">Similar Titles</a></li>
					<li><a id="list-series-tab" href="#list-series" style="display:none">Also in this series</a></li>
				</ul>
				
				{assign var="scrollerName" value="SimilarTitlesVuFind"}
				{assign var="wrapperId" value="similar-titles-vufind"}
				{assign var="scrollerVariable" value="similarTitleVuFindScroller"}
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
					$("#moredetails-tabs").tabs();
					
					{/literal}
					{if $defaultDetailsTab}
						$("#moredetails-tabs").tabs('select', '{$defaultDetailsTab}');
					{/if}
					
					similarTitleVuFindScroller = new TitleScroller('titleScrollerSimilarTitles', 'SimilarTitles', 'similar-titles');
					similarTitleVuFindScroller.loadTitlesFrom('{$url}/Search/AJAX?method=GetListTitles&id=similarTitles&recordId={$id}&scrollerName=SimilarTitles', false);
		
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
				{assign var="wrapperId" value="series"}
				{assign var="scrollerVariable" value="seriesScroller"}
				{assign var="fullListLink" value="$path/Record/$id/Series"}
				{include file=titleScroller.tpl}
				
			</div>
			
		{/if}
		
		<div id="moredetails-tabs">
			{* Define tabs for the display *}
			<ul>
				<li><a href="#holdingstab">{translate text="Copies"}</a></li>
				{if $notes}
					<li><a href="#notestab">{translate text="Notes"}</a></li>
				{/if}
				{if $showAmazonReviews || $showStandardReviews}
					<li><a href="#reviewtab">{translate text="Reviews"}</a></li>
				{/if}
				<li><a href="#readertab">{translate text="Reader Comments"}</a></li>
				<li><a href="#citetab">{translate text="Citation"}</a></li>
				<li><a href="#stafftab">{translate text="Staff View"}</a></li>
			</ul>
			
			{* Display the content of individual tabs *}
			{if $notes}
				<div id ="notestab">
					<ul class='notesList'>
					{foreach from=$notes item=note}
						<li>{$note}</li>
					{/foreach}
					</ul>
				</div>
			{/if}
			
			
			<div id="reviewtab">
				<div id = "staffReviewtab" >
				{include file="$module/view-staff-reviews.tpl"}
				</div>
				 
				{if $showAmazonReviews || $showStandardReviews}
				<h4>Professional Reviews</h4>
				<div id='reviewPlaceholder'></div>
				{/if}
			</div>
			
			{if $showComments == 1}
				<div id = "readertab" >
					<div style ="font-size:12px;" class ="alignright" id="addReview"><span id="userreviewlink" class="add" onclick="$('#userreview{$shortId}').slideDown();">Add a Review</span></div>
					<div id="userreview{$shortId}" class="userreview">
						<span class ="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
						<div class='addReviewTitle'>Add your Review</div>
						{assign var=id value=$id}
						{include file="$module/submit-comments.tpl"}
					</div>
					{include file="$module/view-comments.tpl"}
				</div>
			{/if}
			
			<div id = "citetab" >
				{include file="$module/cite.tpl"}
			</div>
			
			<div id = "stafftab">
				{include file=$staffDetails}
			</div>
			
			<div id = "holdingstab">
		{if $internetLinks}
		<h3>{translate text="Internet"}</h3>
		{foreach from=$internetLinks item=internetLink}
		{if $proxy}
		<a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a><br/>
		{else}
		<a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a><br/>
		{/if}
		{/foreach}
		{/if}
				<div id="holdingsPlaceholder"></div>
				{if $enablePurchaseLinks == 1 && !$purchaseLinks}
					<div class='purchaseTitle button'><a href="#" onclick="return showPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
				{/if}
				
			</div>
		</div> {* End of tabs*}
		
		{literal}
		<script type="text/javascript">
			$(function() {
				$("#moredetails-tabs").tabs();
			});
		</script>
		{/literal}
		
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
