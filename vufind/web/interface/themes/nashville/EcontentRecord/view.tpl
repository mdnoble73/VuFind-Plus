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
	{if (isset($title)) }
		//alert("{$title}");
	{/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
		getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if}

<div id="record-header">
			
            {*{if isset($previousId)}
				<div id="previousRecordLink"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|replace:'&':'&amp;'|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
			{/if}*}
			
            <div id="recordTitleAuthorGroup">
				{* Display Title *}
				<div id='recordTitle'>{$eContentRecord->title|removeTrailingPunctuation|escape}{if $eContentRecord->subTitle}: {$eContentRecord->subTitle|removeTrailingPunctuation|escape}{/if}
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
					<div class="recordAuthor">
						<span class="resultLabel">by</span>&nbsp;
						<span class="resultValue"><a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></span>
					</div>
				{/if}

			</div>
			<div id ="recordTitleRight">
				{*{if isset($nextId)}
					<div id="nextRecordLink"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|replace:'&':'&amp;'|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
				{/if}
				{if $lastsearch}
				<div id="returnToSearch">
					<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a>
				</div>
				{/if}*}
	 		</div>
	 	</div>
            
<div id="sidebarRight">	<div id="sidebar">
				<div id="recordTools">
					{include file="EcontentRecord/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUr}
				</div>
                
		{include file="EcontentRecord/tag_sidegroup.tpl"}


        {if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 0}
			<div class="sidegroup" id="inProspectorSidegroup" style="display:none">
				{* Display in Prospector Sidebar *}
				<div id="inProspectorPlaceholder"></div>
			</div>
		{/if}

		{if $classicId}
			<div id = "classicViewLink"><a href ="{$classicUrl}/record={$classicId|escape:"url"}&amp;searchscope={$millenniumScope}" rel="external" onclick="trackEvent('Outgoing Link', 'Classic', '{$classicId}');window.open (this.href, 'child'); return false">Classic View</a></div>
		{/if}
		{if $eContentRecord->sourceUrl}
			<div id="econtentSource">
				<a href="{$eContentRecord->sourceUrl|replace:'&':'&amp;'}">Access original files</a>
			</div>
		{/if}


	</div> {* End sidebar *}
</div> {* End sidebar right *}

	<div id="main-content" class="full-result-content">
		
			<div id="image-column">
			{* Display Book Cover *}
			{if $user->disableCoverArt != 1}

				<div id = "recordcover">
					<div class="recordcoverWrapper">
						<img alt="{translate text='Book Cover'}" class="recordcover" src="{$bookCoverUrl}" />
						<div id="goDeeperLink" class="godeeper" style="display:none">
							<a href="{$path}/EcontentRecord/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightbox('{$path}/EcontentRecord/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;">
							<img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png" /></a>
						</div>
					</div>
				</div>
			{/if}

			{* Add to Wish List *}
			<div class='addToWishListLink' id="addToWishList{$id|escape:"url"}" style="display:none">
				<a href="{$path}/EcontentRecord/{$id|escape:"url"}/AddToWishList" class="button">{translate text="Add To Wish List"}</a>
			</div>

			{if $goldRushLink}
			<div class ="titledetails">
				<a href='{$goldRushLink}' >Check for online articles</a>
			</div>
			{/if}

			{* Let the user rate this title *}
			{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$id shortId=$id ratingData=$ratingData showFavorites=0}

		</div> {* End image column *}

		<div id="record-details-column">
			<div id="record-details-header">
				<div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading...</div>
				{if $enableProspectorIntegration == 1}
					<div id="prospectorHoldingsPlaceholder"></div>
				{/if}


				<div class="clearer">&nbsp;</div>
			</div>

			{if $cleanDescription}
			<div class="resultInformation">
				<div class="resultInformationLabel">{translate text='Description'}</div>
				<div class="recordDescription">
					{$cleanDescription}
				</div>
			</div>
			{/if}

			{if count($subjectList) > 0}
			<div class="resultInformation">
				<div class="resultInformationLabel">{translate text='Subjects'}</div>
				<div class="recordSubjects">
					{foreach from=$subjectList item=subjectListItem name=loop}
							<a href="{$path}/Search/Results?lookfor=%22{$subjectListItem|escape:'url'}%22&amp;basicType=Subject">{$subjectListItem|escape}</a>
						<br />
					{/foreach}
				</div>
			</div>
			{/if}

		</div>

		<div id="relatedTitleInfo" style="display:none">

			{assign var="scrollerName" value="Series"}
			{assign var="scrollerTitle" value="Also in this Series"}
			{assign var="wrapperId" value="series"}
			{assign var="scrollerVariable" value="seriesScroller"}
			{assign var="fullListLink" value="$path/EcontentRecord/$id/Series"}
			{include file="titleScroller.tpl"}
		</div>

		<a id="detailsTab" href="#detailsTab"></a>
		<div id="moredetails-tabs">
			{* Define tabs for the display *}
			<ul>
				<li id="formatstabLink"><a href="#formatstab">{translate text="Formats"}</a></li>
                <li id="detailstab_label"><a href="#detailstab">{translate text="Title Details"}</a></li>
				{if $enableMaterialsRequest || is_array($otherEditions) }
					<li id="otherEditionsTab_label"><a href="#otherEditionsTab">{translate text="Other Formats"}</a></li>
				{/if}
				{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
					<li><a href="#prospectorTab">{translate text="In Prospector"}</a></li>
				{/if}
				{if $notes}
					<li><a href="#notestab">{translate text="Notes"}</a></li>
				{/if}
				{if $showAmazonReviews || $showStandardReviews || $showComments}
					{foreach from=$reviews key=key item=reviewTabInfo}
						<li><a href="#{$key}">{translate text=$reviewTabInfo.tabName}</a></li>
					{/foreach}
				{/if}
				<li><a href="#citetab">{translate text="Citation"}</a></li>
				<li id="copiestabLink"><a href="#copiestab">{translate text="Copies"}</a></li>
				{if $staffDetails != null}
					<li><a href="#stafftab">{translate text="Staff View"}</a></li>
				{/if}
			</ul>

			<div id="formatstab">
				<div id="formatsPlaceholder">Loading...</div>
				<div id="additionalFormatActions">
					{if $showOtherEditionsPopup}
					<div class="otherEditionCopies button">
						<div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummaries('{$id}', true)">{translate text="Other Formats and Languages"}</a></div>
					</div>
					{/if}
					{if $enablePurchaseLinks == 1}
						<div class='purchaseTitle button'><a href="#" onclick="return showEcontentPurchaseOptions('{$id}');">{translate text='Buy a Copy'}</a></div>
					{/if}
			 	</div>
			</div>
            
             <div id="detailstab">
                    {if $eContentRecord->author}
                        <div class="sidebarLabel">{translate text='Main Author'}:</div>
                        <div class="sidebarValue"><a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></div>
                    {/if}
        
                    {if count($additionalAuthorsList) > 0}
                        <div class="sidebarLabel">{translate text='Additional Authors'}:</div>
                        {foreach from=$additionalAuthorsList item=additionalAuthorsListItem name=loop}
                            <div class="sidebarValue"><a href="{$path}/Author/Home?author={$additionalAuthorsListItem|escape:"url"}">{$additionalAuthorsListItem|escape}</a></div>
                        {/foreach}
                    {/if}
        
                    {if $eContentRecord->publishDate || $eContentRecord->publisher || $eContentRecord->publishLocation}
                        <div class="sidebarLabel">{translate text='Published'}:</div>
                        <div class="sidebarValue">{$eContentRecord->publisher|escape} {$eContentRecord->publishLocation|escape} {$eContentRecord->publishDate|escape}</div>
                    {/if}
        
                    <div class="sidebarLabel">{translate text='Format'}:</div>
                    {if is_array($eContentRecord->format())}
                     {foreach from=$eContentRecord->format() item=displayFormat name=loop}
                         <div class="sidebarValue"><span class="icon {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$displayFormat}</span></div>
                     {/foreach}
                    {else}
                        <div class="sidebarValue"><span class="icon {$eContentRecord->format()|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$eContentRecord->format}</span></div>
                    {/if}
        
                    {if $eContentRecord->physicalDescription}
                        <div class="sidebarLabel">{translate text='Physical Desc'}:</div>
                        {foreach from=$eContentRecord->physicalDescription item=physicalDescription name=loop}
                            <div class="sidebarValue">{$physicalDescription|escape}</div>
                        {/foreach}
                    {/if}
        
                    <div class="sidebarLabel">{translate text='Language'}:</div>
                    <div class="sidebarValue">{$eContentRecord->language|escape}</div>
        
                    {if $eContentRecord->edition}
                        <div class="sidebarLabel">{translate text='Edition'}:</div>
                        <div class="sidebarValue">{$eContentRecord->edition|escape}</div>
                    {/if}
        
                    {if count($lccnList) > 0}
                        <div class="sidebarLabel">{translate text='LCCN'}:</div>
                        {foreach from=$lccnList item=lccnListItem name=loop}
                            <div class="sidebarValue">{$lccnListItem|escape}</div>
                        {/foreach}
                    {/if}
        
                    {if count($isbnList) > 0}
                        <div class="sidebarLabel">{translate text='ISBN'}:</div>
                        {foreach from=$isbnList item=isbnListItem name=loop}
                            <div class="sidebarValue">{$isbnListItem|escape}</div>
                        {/foreach}
                    {/if}
        
                    {if count($issnList) > 0}
                        <div class="sidebarLabel">{translate text='ISSN'}:</div>
                        {foreach from=$issnList item=issnListItem name=loop}
                            <div class="sidebarValue">{$issnListItem|escape}</div>
                        {/foreach}
                    {/if}
        
                    {if count($upcList) > 0}
                        <div class="sidebarLabel">{translate text='UPC'}:</div>
                        {foreach from=$upcList item=upcListItem name=loop}
                            <div class="sidebarValue">{$upcListItem|escape}</div>
                        {/foreach}
                    {/if}
        
                    {if count($seriesList) > 0}
                        <div class="sidebarLabel">{translate text='Series'}:</div>
                        {foreach from=$seriesList item=seriesListItem name=loop}
                            <div class="sidebarValue"><a href="{$path}/Search/Results?lookfor=%22{$seriesListItem|escape:"url"}%22&amp;basicType=Series">{$seriesListItem|escape}</a></div>
                        {/foreach}
                    {/if}
        
                    {if count($topicList) > 0}
                        <div class="sidebarLabel">{translate text='Topic'}:</div>
                        {foreach from=$topicList item=topicListItem name=loop}
                            <div class="sidebarValue"><a href="{$path}/Search/Results?lookfor=%22{$topicListItem|escape:"url"}%22&amp;basicType=Subject">{$topicListItem|escape}</a></div>
                        {/foreach}
                    {/if}
        
                    {if count($genreList) > 0}
                    <div class="sidebarLabel">{translate text='Genre'}:</div>
                    {foreach from=$genreList item=genreListItem name=loop}
                        <div class="sidebarValue">{$genreListItem|escape}</div>
                    {/foreach}
                    {/if}
        
                    {if count($regionList) > 0}
                    <div class="sidebarLabel">{translate text='Region'}:</div>
                    {foreach from=$regionList item=regionListItem name=loop}
                        <div class="sidebarValue">{$regionListItem|escape}</div>
                    {/foreach}
                    {/if}
        
                    {if count($eraList) > 0}
                    <div class="sidebarLabel">{translate text='Era'}:</div>
                    {foreach from=$eraList item=eraListItem name=loop}
                        <div class="sidebarValue">{$eraListItem|escape}</div>
                    {/foreach}
                    {/if}
                </div>

			{if $enableMaterialsRequest || is_array($otherEditions) }
				<div id="otherEditionsTab">
					{include file='Resource/otherEditions.tpl' otherEditions=$editionResources}
				</div>
			{/if}

			{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
				<div id="prospectorTab">
					<div id="inProspectorPlaceholder"></div>
				</div>
			{/if}

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

			{foreach from=$reviews key=key item=reviewTabInfo}
				<div id="{$key}">
					{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
						<div>
							<span class="button"><a href='{$path}/EditorialReview/Edit?recordId=econtentRecord{$id}'>Add Editorial Review</a></span>
						</div>
					{/if}

					{if $key == 'reviews'}
						{if $showComments}
							{include file="$module/view-comments.tpl"}

							<div id = "staffReviewtab" >
								{include file="Record/view-staff-reviews.tpl"}
							</div>
						{/if}
						{if $showStandardReviews}
							<div id='reviewPlaceholder'></div>
						{/if}
					{/if}

					{foreach from=$reviewTabInfo.reviews item=review}
						{assign var=review value=$review}
						{include file="Resource/view-review.tpl"}
					{/foreach}
				</div>
			{/foreach}

			<div id = "citetab" >
				{include file="Record/cite.tpl"}
			</div>

			<div id = "copiestab">
				<div id="copiesPlaceholder">Loading...</div>
			</div>

			{if $staffDetails}
				<div id = "stafftab">
					{include file=$staffDetails}

					{if $user && $user->hasRole('opacAdmin')}
						<br/>
						<a href="{$path}/EcontentRecord/{$id|escape:"url"}/AJAX?method=downloadMarc" class="button">{translate text="Download Marc"}</a>
					{/if}
				</div>
			{/if}
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
		 item: "{/literal}econtentRecord{$id|escape}{literal}"
	});
	</script>
	{/literal}
{/if}
{/strip}