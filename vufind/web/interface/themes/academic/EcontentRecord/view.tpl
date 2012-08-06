{strip}
{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}

<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetEContentHoldingsInfo('{$id|escape:"url"}');
	{if $isbn || $upc}
		GetEContentEnrichmentInfo('{$id|escape:"url"}', '{$isbn10|escape:"url"}', '{$upc|escape:"url"}');
	{/if}
	{if $isbn && ($showComments || $showAmazonReviews || $showStandardReviews)}
		GetReviewInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}');
	{/if}
		{if $enablePospectorIntegration == 1}
		GetEContentProspectorInfo('{$id|escape:"url"}');
	{/if}
	{if $user}
		redrawSaveStatus();
	{/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
		getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

<div id="page-content" class="content">
	<div class="toolbar">
		<ul>
			{if isset($previousId)}
				<li><a href="{$url}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" class="previousLink" title="{if !$previousTitle}{translate text='Title not available'}{else}{$previousTitle|truncate:180:"..."}{/if}">{translate text="Previous"}</a></li>
			{/if}
			{if $showTextThis == 1}
				<li><a href="{$path}/EcontentRecord/{$id|escape:"url"}/SMS" class="sms" id="smsLink" onclick="ajaxLightbox('{$path}/EcontentRecord/{$id|escape}/SMS?lightbox', '#citeLink'); return false;">{translate text="Text this"}</a></li>
			{/if}
			{if $showEmailThis == 1}
				<li><a href="{$path}/EcontentRecord/{$id|escape:"url"}/Email" class="mail" id="mailLink" onclick="ajaxLightbox('{$path}/EcontentRecord/{$id|escape}/Email?lightbox', '#citeLink'); return false;">{translate text="Email this"}</a></li>
			{/if}
			{if is_array($exportFormats) && count($exportFormats) > 0}
				<li>
					<a href="{$path}/EcontentRecord/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onclick="toggleMenu('exportMenu'); return false;">{translate text="Export Record"}</a><br />
					<ul class="menu" id="exportMenu">
						{foreach from=$exportFormats item=exportFormat}
							<li><a {if $exportFormat=="RefWorks"} {/if}href="{$path}/EcontentRecord/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
						{/foreach}
					</ul>
				</li>
			{/if}
			{if $showFavorites == 1}
				<li id="saveLink"><a href="{$path}/Resource/Save?id={$id|escape:"url"}&amp;source=eContent" class="fav" onclick="getSaveToListForm('{$id|escape}', 'eContent'); return false;">{translate text="Add to favorites"}</a></li>
			{/if}
			{if !empty($addThis)}
				<li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></li>
			{/if}
			<li id="HoldingsLink"><a href="#holdings" class ="holdings">{translate text="Holdings"}</a></li>
			{if isset($nextId)}
				<li><a href="{$url}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" class="nextLink" title="{if !$nextTitle}{translate text='Title not available'}{else}{$nextTitle|truncate:180:"..."}{/if}">{translate text="Next"}</a></li>
			{/if}
		</ul>
	</div>

	<div id="sidebar">
		{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 0}
			<div class="sidegroup" id="inProspectorSidegroup" style="display:none">
				{* Display in Prospector Sidebar *}
				<div id="inProspectorPlaceholder"></div>
			</div>
		{/if}
		
		{if is_array($editions) && !$showOtherEditionsPopup}
			<div class="sidegroup" id="otherEditionsSidegroup">
				<h4>{translate text="Other Editions"}</h4>
				{foreach from=$editions item=edition}
					<div class="sidebarLabel">
						{if $edition.recordtype == 'econtentRecord'}
							<a href="{$path}/EcontentRecord/{$edition.id|replace:'econtentRecord':''|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
						{else}
							<a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
						{/if}
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
		
		<div class="sidegroup" id="similarAuthorsSidegroup">
			<div id="similarAuthorPlaceholder"></div>
		</div>
		
		<div class="sidegroup" id="similarTitlesSidegroup" style='display:none'>
			{* Display either similar tiles from novelist or from the catalog*}
			<div id="similarTitlePlaceholder"></div>
		</div>
		
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
		
		{if $classicId}
			<div id = "classicViewLink"><a href ="{$classicUrl}/record={$classicId|escape:"url"}" rel="external" onclick="window.open (this.href, 'child'); return false">Classic View</a></div>
		{/if}
		
		{if $linkToAmazon == 1 && $isbn}
			<div class="titledetails">
				<a href="http://amazon.com/dp/{$isbn|@formatISBN}" class='amazonLink'> {translate text = "View on Amazon"}</a>
			</div>
		{/if}
	</div>
	
	{if $error}<p class="error">{$error}</p>{/if} 
	<div id="main-content" class="full-result-content">
		<div id = "fullcontent">
			<div id='fullRecordSummaryAndImage'>
				<div class="clearer"></div>
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
					<a href="{$path}/EcontentRecord/{$id|escape:"url"}/Hold" class="button">{translate text="Place Hold"}</a>
				</div>
			
				{* Checkout link *}
				<div class='checkoutLink' id="checkout{$id|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout" class="button">{translate text="Checkout"}</a>
				</div>
				
				{* Add to Wish List *}
				<div class='addToWishListLink' id="addToWishList{$id|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$id|escape:"url"}/AddToWishList" class="button">{translate text="Add To Wish List"}</a>
				</div>
				
				{if $showOtherEditionsPopup}
					<div class="otherEditionCopies">
						<div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummaries('{$id}', true)">{translate text="Other Formats and Languages"}</a></div>
					</div>
				{/if}
			
				{if $showRatings}
					<div id="myrating" class="stat">
						<div class="statVal">
							<div class="ui-rater">
								<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:63px">&nbsp;</span></span>
							</div>
						</div>
						<script type="text/javascript">
						$(function() {literal} { {/literal}
								$('#myrating').rater({literal}{ {/literal} module:'EcontentRecord', rating:'{if $user}{$ratingData.user}{else}{$ratingData.average}{/if}', recordId: '{$id}', postHref: '{$path}/EcontentRecord/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
						{literal} } {/literal});
						</script>
					</div>
				{/if}
			</div>
			
			<div id='fullRecordTitleDetails'>
				{* Display Title *}
				<div id='recordTitle'>{$eContentRecord->title|regex_replace:"/(\/|:)$/":""|escape} 
				{if $user && $user->hasRole('epubAdmin')}
				{if $eContentRecord->status != 'active'}<span id="eContentStatus">({$eContentRecord->status})</span>{/if}
				<span id="editEContentLink"><a href='{$path}/EcontentRecord/{$id}/Edit'>(edit)</a></span>
				{if $eContentRecord->status != 'archived' && $eContentRecord->status != 'deleted'}
					<span id="archiveEContentLink"><a href='{$path}/EcontentRecord/{$id}/Archive' onclick="return confirm('Are you sure you want to archive this record?	The record should not have any holds or checkouts when it is archived.')">(archive)</a></span>
				{/if}
				{if $eContentRecord->status != 'deleted'}
					<span id="deleteEContentLink"><a href='{$path}/EcontentRecord/{$id}/Delete' onclick="return confirm('Are you sure you want to delete this record?	The record should not have any holds or checkouts when it is deleted.')">(delete)</a></span>
				{/if}
				{/if}
				</div>
		
				{* Display more information about the title*}
				{if $eContentRecord->author}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Main Author'}:</span>
					<span class="resultValue"><a href="{$url}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></span>
				</div>
				{/if}
		
				{if count($additionalAuthorsList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Contributors'}:</span>
						<span class="resultValue">
						{foreach from=$additionalAuthorsList item=additionalAuthorsListItem name=loop}
							<a href="{$path}/Author/Home?author={$additionalAuthorsListItem|escape:"url"}">{$additionalAuthorsListItem|escape}</a>{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
						</span>
					</div>
				{/if}
				
				{if $eContentRecord->publisher}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Publisher'}:</span>
						<span class="resultValue">{$eContentRecord->publisher|escape}</span>
					</div>
				{/if}
				
				{if $eContentRecord->publishDate}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Published'}:</span>
						<span class="resultValue">{$eContentRecord->publishDate|escape}</span>
					</div>
				{/if}
				
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Format'}:</span>
					{if is_array($eContentRecord->format())}
					 {foreach from=$eContentRecord->format() item=displayFormat name=loop}
						 <span class="resultValue"><span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span></span>
					 {/foreach}
					{else}
						<span class="resultValue"><span class="iconlabel {$eContentRecord->format()|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$eContentRecord->format}</span></span>
					{/if}
				</div>
		
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Language'}:</span>
					<span class="resultValue">{$eContentRecord->language|escape}</span>
				</div>
				
				{if $eContentRecord->edition}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Edition'}:</span>
						<span class="resultValue">{$eContentRecord->edition|escape}</span>
					</div>
				{/if}
	
				{if count($lccnList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='LCCN'}:</span>
						{foreach from=$lccnList item=lccnListItem name=loop}
							<span class="resultValue">{$lccnListItem|escape}</span>
						{/foreach}
					</div>
				{/if}
	
				{if count($isbnList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='ISBN'}:</span>
						{foreach from=$isbnList item=isbnListItem name=loop}
							<span class="resultValue">{$isbnListItem|escape}</span>
						{/foreach}
					</div>
				{/if}
	
				{if count($issnList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='ISSN'}:</span>
						{foreach from=$issnList item=issnListItem name=loop}
							<span class="resultValue">{$issnListItem|escape}</span>
						{/foreach}
					</div>
				{/if}
					 
				{if count($upcList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='UPC'}:</span>
						{foreach from=$upcList item=upcListItem name=loop}
							<span class="resultValue">{$upcListItem|escape}</span>
						{/foreach}
					</div>
				{/if}
				
				{if count($topicList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Topic'}:</span>
						{foreach from=$topicList item=topicListItem name=loop}
							<span class="resultValue"><a href="{$path}/Search/Results?lookfor=%22{$topicListItem|escape:"url"}%22&amp;basicType=Subject">{$topicListItem|escape}</a></span>
						{/foreach}
					</div>
				{/if}
	
				{if count($genreList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Genre'}:</span>
						{foreach from=$genreList item=genreListItem name=loop}
							<span class="resultValue">{$genreListItem|escape}</span>
						{/foreach}
					</div>
				{/if}	
	
				{if count($regionList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Region'}:</span>
						{foreach from=$regionList item=regionListItem name=loop}
							<span class="resultValue">{$regionListItem|escape}</span>
						{/foreach}
					</div>
				{/if}
	
				{if count($eraList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Era'}:</span>
						{foreach from=$eraList item=eraListItem name=loop}
							<span class="resultValue">{$eraListItem|escape}</span>
						{/foreach}
					</div>
				{/if} 
			
				<div class="resultInformation" ><span class="resultLabel">{translate text='Location'}:</span><span class="resultValue boldedResultValue" id="locationValue">Online</span></div>
				<div class="resultInformation" ><span class="resultLabel">{translate text='Status'}:</span><span class="resultValue" id="statusValue">Loading...</span></div>
					
				{if count($subjectList) > 0}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Subjects'}</span>
					<span class="resultValue">
						{foreach from=$subjectList item=subjectListItem name=loop}
								<a href="{$path}/Search/Results?lookfor=%22{$subjectListItem|escape:'url'}%22&amp;type=Subject">{$subjectListItem|escape}</a>
							<br />
						{/foreach}
					</span>
				</div>
				{/if}
				
				{if $eContentRecord->description}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Summary'}</span>
					<span class="resultValue">
						{$eContentRecord->description|escape}
					</span>
				</div>
				{/if}
			</div>	
			{* End Title *}
			<div id = "titleblock">
				
				{if count($seriesList) > 0}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Series'}:</<span class="resultLabel">
						{foreach from=$seriesList item=seriesListItem name=loop}
							<span class="resultValue"><a href="{$path}/Search/Results?lookfor=%22{$seriesListItem|escape:"url"}%22&amp;basicType=Series">{$seriesListItem|escape}</a></span>
						{/foreach}
					</div>
				{/if} 
			
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
							<a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=eContent" class="tool add" onclick="GetAddTagForm('{$id|escape}', 'eContent'); return false;">{translate text="Add Tag"}</a>
						</div>
					</div>
				{/if}
			</div> {* End titleblock *}
			<div>
				<div class="clearer">&nbsp;</div>
				<div id="seriesPlaceholder"></div>
				<div id="moredetails-tabs">
					{* Define tabs for the display *}
					<ul>
						<li><a href="#holdingstab">{translate text="Copies"}</a></li>
						{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
							<li><a href="#prospectorTab">{translate text="In Prospector"}</a></li>
						{/if}
						{if $notes}
							<li><a href="#notestab">{translate text="Notes"}</a></li>
						{/if}
						{if $showAmazonReviews || $showStandardReviews || $showComments}
							<li><a href="#reviewtab">{translate text="Reviews"}</a></li>
						{/if}
						{if $showComments}
						<li><a href="#readertab">{translate text="Reader Comments"}</a></li>
						{/if}
						<li><a href="#citetab">{translate text="Citation"}</a></li>
						<li><a href="#stafftab">{translate text="Staff View"}</a></li>
					</ul>
			
					{* Display the content of individual tabs *}
					<div id = "holdingstab">
						<div id="holdingsPlaceholder">Loading...</div>
						{if $showOtherEditionsPopup}
						<div class="otherEditionCopies">
							<div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummaries('{$id}', true)">{translate text="Other Formats and Languages"}</a></div>
						</div>
						{/if}
						{if $enablePurchaseLinks == 1}
							<div class='purchaseTitle button'><a href="#" onclick="return showEcontentPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
						{/if}
					 {if $eContentRecord->sourceUrl}
						<div id="econtentSource">
							<a href="{$eContentRecord->sourceUrl}">Access original files</a>
						</div>
						{/if}
					</div>
					
					{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
						<div id="prospectorTab">
							{* Display in Prospector Sidebar *}
							<div id="inProspectorPlaceholder"></div>
						</div>
					{/if}
					
					{if $notes}
						<div id ="notestab">
							<ul class='notesList'>
							{foreach from=$notes item=note}
								<li>{$note}</li>
							{/foreach}
							</ul>
						</div>
					{/if}
			
					{if $showAmazonReviews || $showStandardReviews || $showComments}
					<div id="reviewtab">
						{if $showComments}
						<div id = "staffReviewtab" >
						{include file="Record/view-staff-reviews.tpl"}
						</div>
						{/if}
						 
						{if $showAmazonReviews || $showStandardReviews}
						<h4>Professional Reviews</h4>
						<div id='reviewPlaceholder'></div>
						{/if}
					</div>
					{/if}
					
					{if $showComments == 1}
						<div id = "readertab" >
							<div style ="font-size:12px;" class ="alignright" id="addReview"><span id="userreviewlink" class="add" onclick="$('#userreview{$id}').slideDown();">Add a Review</span></div>
							<div id="userreview{$id}" class="userreview">
								<span class ="alignright unavailable closeReview" onclick="$('#userreview{$id}').slideUp();" >Close</span>
								<div class='addReviewTitle'>Add your Review</div>
								{assign var=id value=$id}
								{include file="EcontentRecord/submit-comments.tpl"}
							</div>
							{include file="EcontentRecord/view-comments.tpl"}
							
							{* Chili Fresh Reviews *}
							{if $chiliFreshAccount && ($isbn || $upc || $issn)}
								<h4>Chili Fresh Reviews</h4>
								{if $isbn}
								<div class="chili_review" id="isbn_{$isbn10}"></div>
								<div id="chili_review_{$isbn10}" style="display:none" align="center" width="100%"></div>
								{elseif $upc}
								<div class="chili_review_{$upc}" id="isbn"></div>
								<div id="chili_review_{$upc}" style="display:none" align="center" width="100%"></div>
								{elseif $issn}
								<div class="chili_review_{$issn}" id="isbn"></div>
								<div id="chili_review_{$issn}" style="display:none" align="center" width="100%"></div>
								{/if}
							{/if}
						</div>
					{/if}
			
					<div id = "citetab" >
						{include file="Record/cite.tpl"}
					</div>
			
					{if $eContentRecord->marcRecord}
						<div id = "stafftab">
							{include file=$staffDetails}
						</div>
					{/if}
				</div> {* End of tabs*}
			</div>
		</div>
	</div>
	{literal}
	<script type="text/javascript">
		$(function() {
			$("#moredetails-tabs").tabs();
		});
	</script>
	{/literal}
</div>

{/strip}