{strip}

{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}

<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetHoldingsInfoMSC('{$id|escape:"url"}');
	{if $isbn || $upc}
		GetEnrichmentInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}', '{$upc|escape:"url"}', {$showSeriesAsTab});
	{/if}
	{if $isbn && ($showComments || $showAmazonReviews || $showStandardReviews)}
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
	<div class="toolbar">
		<ul>
			{if isset($previousId)}
				<li><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" class="previousLink" title="{if !$previousTitle}{translate text='Title not available'}{else}{$previousTitle|truncate:180:"..."}{/if}">{translate text="Previous"}</a></li>
			{/if}
			{if $showTextThis == 1}
				<li><a href="{$path}/Record/{$id|escape:"url"}/SMS" class="sms" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox", "#smsLink"); return false;'>{translate text="Text this"}</a></li>
			{/if}
			{if $showEmailThis == 1}
				<li><a href="{$path}/Record/{$id|escape:"url"}/Email" class="mail" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/Email?lightbox", "#mailLink"); return false;'>{translate text="Email this"}</a></li>
			{/if}
			{if is_array($exportFormats) && count($exportFormats) > 0}
				<li>
					<a href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onclick="toggleMenu('exportMenu'); return false;">{translate text="Export Record"}</a><br />
					<ul class="menu" id="exportMenu">
					{foreach from=$exportFormats item=exportFormat}
						<li><a {if $exportFormat=="RefWorks"}target="{$exportFormat}Main" {/if}href="{$path}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
					{/foreach}
					</ul>
				</li>
			{/if}
			{if $showFavorites == 1}
				<li id="saveLink"><a href="{$path}/Resource/Save?id={$id|escape:"url"}&amp;source=VuFind" class="fav" onclick="getSaveToListForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add to favorites"}</a></li>
			{/if}
			{if $enableBookCart == 1}
				<li id="bookCartLink"><a href="#" class="cart" onclick="addToBag('{$id|escape}', '{$recordTitleSubtitle|replace:'"':''|escape:'javascript'}', this);">{translate text="Add to book cart"}</a></li>
			{/if}
			{if !empty($addThis)}
				<li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></li>
			{/if}
			<li id="HoldingsLink"><a href="#holdings" class ="holdings">{translate text="Holdings"}</a></li>
			{if isset($nextId)}
				<li><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" class="nextLink" title="{if !$nextTitle}{translate text='Title not available'}{else}{$nextTitle|truncate:180:"..."}{/if}">{translate text="Next"}</a></li>
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
			<div class="sidegroup">
				<h4>{translate text="Other Editions"}</h4>
				<ul class="similar">
					{foreach from=$editions item=edition}
					<li>
						{if is_array($edition.format)}
							<span class="{$edition.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
						{else}
							<span class="{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">
						{/if}
						<a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
						</span>
						{$edition.edition|escape}
						{if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
					</li>
					{/foreach}
				</ul>
			</div>
		{/if}
	
		<div id='similarAuthorsSidegroup' class="sidegroup" style='display:none'>
			<span class="resultValue" id ="similarAuthorPlaceholder"></span>
		</div>
	
		<div id='similarTitles' class="sidegroup" style='display:none'>
			{* Display either similar tiles from novelist or from the catalog*}
			<div id="similarTitlePlaceholder" style='display:none'></div>
		</div>
	
		{if is_array($similarRecords)}
			<div id="relatedTitles" class="sidegroup">
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
						{if $similar.author}<br />{translate text='By'}: {$similar.author|escape}{/if}
						</span>
					</li>
					{/foreach}
				</ul>
			</div>
		{/if}
	
		<div id = "classicViewLink">
			<a href ="{$classicUrl}/record={$classicId|escape:"url"}" onclick="window.open (this.href, 'child'); return false">Classic View</a>
		</div>
		
		{if $linkToAmazon == 1 && $isbn}
			<div class="titledetails">
				<a href="http://amazon.com/dp/{$isbn|@formatISBN}" class='amazonLink' onclick="window.open (this.href, 'child'); return false"> {translate text = "View on Amazon"}</a>
			</div>
		{/if}
	</div>
	{if $error}<p class="error">{$error}</p>{/if} 
				
	<div id="main-content" class="full-result-content">
		<div id = "fullcontent">
			<div id='fullRecordSummaryAndImage'>
				<div class="clearer"></div>
				{* Display Book Cover *}
				{if $isbn || $upc}
					<div class="recordcoverWrapper">
						<a href="{$path}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=large&amp;upc={$upc}&amp;category={$format_category|escape:"url"}&amp;format={$recordFormat.0|escape:"url"}">							
							<img alt="{translate text='Book Cover'}" class="recordcover" src="{$path}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=medium&amp;upc={$upc}&amp;category={$format_category|escape:"url"}&amp;format={$recordFormat.0|escape:"url"}" />
						</a>
						<div id="goDeeperLink" class="godeeper" style="display:none">
							<a href="{$path}/Record/{$id|escape:"url"}/GoDeeper" onclick="ajaxLightbox('{$path}/Record/{$id|escape}/GoDeeper?lightbox', null,'5%', '90%', 50, '85%'); return false;">
								<img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png"/>
							</a>
						</div>
					</div>
				{/if}
				<div class='requestThisLink' style='display:none'>
					<a href="{$path}/Record/{$id|escape:"url"}/Hold" class="holdRequest  button" style="display:inline-block;font-size:11pt;margin-top:15px;">{translate text="Request This"}</a><br />
				</div>
				
				{if $showRatings == 1}
					<div id="ratingSummary">
						<span class="ratingHead">Patron Rating</span><br /><br />
						<div id="rate{$noDot}" class="stat">
							<div class="statVal">
								<span class="ui-rater">
									<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px">&nbsp;</span></span>
									<span class="ui-rater-rating">{$ratingData.average|string_format:"%.2f"}</span>&#160;(<span class="ui-rater-rateCount">{$ratingData.count}</span>)
								</span>
							</div>
							<script type="text/javascript">
							$(
								function() {literal} { {/literal}
										$('#rate{$noDot}').rater({literal}{ {/literal} rating:'{$ratingData.average}', postHref: '{$path}/Record/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
								{literal} } {/literal}
							);
							</script>
						</div>
						{*
						<span class="smallText">Average Patron Rating</span><br />
						{$ratingData.count} ratings<br />
						<img src="{$path}/{$ratingData.summaryGraph}" alt='Ratings Summary'> 
						*}
						<br />
						<br />
					</div>
				{/if}{* Ratings *}
			</div>
			<div id='fullRecordTitleDetails'>	
				{* Display Title *}
				<h1 class='recordTitle'>{$recordTitleWithAuth|regex_replace:"/(\/|:)$/":""|escape}</h1>
				
				{* Display more information about the title*}
				{if $mainAuthor}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Main Author'}:</span>
					<span class="resultValue"><a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$mainAuthor|escape}</a></span>
				</div>
				{/if}
				
				{if $corporateAuthor}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Corporate Author'}:</span>
					<span class="resultValue">{$corporateAuthor|escape}</span>
				</div>
				{/if}
				
				{assign var=marcField value=$marc->getField('240')}
				{if $marcField}
					<div class="resultInformation">
						<span class="resultLabel">{translate text='Uniform Title:'}</span>
						<span class="resultValue"><a href="{$path}/Union/Search?searchSource=local&basicType=Title&lookfor={$marcField|getvalue:'a'|escape:"url"}{if $marcField|getvalue:'m'}+{$marcField|getvalue:'m'|escape:"url"}{/if}{if $marcField|getvalue:'n'}+{$marcField|getvalue:'n'|escape:"url"}{/if}{if $marcField|getvalue:'o'}+{$marcField|getvalue:'o'|escape:"url"}{/if}">{$marcField|getvalue:'a'}{if $marcField|getvalue:'m'} {$marcField|getvalue:'m'}{/if}{if $marcField|getvalue:'n'} {$marcField|getvalue:'n'}{/if}{if $marcField|getvalue:'o'} {$marcField|getvalue:'o'}{/if}</a></span>
					</div>
				{/if}
				{if $contributors}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Contributors'}:</span>
					<span class="resultValue">
						{foreach from=$contributors item=contributor name=loop}
							<a href="{$path}/Author/Home?author={$contributor|escape:"url"}">{$contributor|escape}</a>{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
					</span>
				</div>
				{/if}
				
				{if $published}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Published'}:</span>
					<span class="resultValue">
						{foreach from=$published item=publish name=loop}
							{$publish|escape}{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
					</span>
				</div>
				{/if}
				
				{if $recordFormat}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Format'}:</span>
					<span class="resultValue">
						{if is_array($recordFormat)}
							{foreach from=$recordFormat item=displayFormat name=loop}
								<span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span>{if !$smarty.foreach.loop.last}, <br/>{/if}
							{/foreach}
						{else}
							<span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span>
						{/if}
					</span>
				</div>
				{/if}
					
				{if $physicalDescriptions}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Physical Desc'}:</span>
					<span class="resultValue">
						{foreach from=$physicalDescriptions item=physicalDescription name=loop}
							{$physicalDescription|escape}{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
					</span>
				</div>
				{/if}
					
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Language'}:</span>
					<span class="resultValue">
						{foreach from=$recordLanguage item=lang}{$lang|escape}<br />{/foreach}
					</span>
				</div>
					
				{if $editionsThis}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Edition'}:</span>
					<span class="resultValue">
						{foreach from=$editionsThis item=edition name=loop}
							{$edition|escape}{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
					</span>
				</div>
				{/if}
					
				{if $isbns}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='ISBN'}:</span>
					<span class="resultValue">
						{foreach from=$isbns item=isbn name=loop}
							{$isbn|escape}{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
					</span>
				</div>
				{/if}
					
				{if $issn}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='ISSN'}:</span>
					<span class="resultValue">
						{$issn}
						{if $goldRushLink}
						<br /><a href='{$goldRushLink}' target='_blank'>Check for online articles</a>
						{/if}
					</span>
				</div>
				{/if}
					
				{if $upc}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='UPC'}:</span>
					<span class="resultValue">
						{$upc|escape}
					</span>
				</div>
				{/if}
					
				<div class="resultInformation" ><span class="resultLabel">{translate text='Location'}:</span><span class="resultValue boldedResultValue" id="locationValue">Loading...</span></div>
				<div class="resultInformation" ><span class="resultLabel">{translate text='Call Number'}:</span><span class="resultValue boldedResultValue" id="callNumberValue">Loading...</span></div>
				<div class="resultInformation" id="downloadLink" style="display:none"><span class="resultLabel">{translate text='Download From'}:</span><span class="resultValue" id="downloadLinkValue">Loading...</span></div>
				<div class="resultInformation" ><span class="resultLabel">{translate text='Status'}:</span><span class="resultValue" id="statusValue">Loading...</span></div>
					
				{if $series}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Series'}:</span>
					<span class="resultValue">
						{foreach from=$series item=seriesItem name=loop}
							<a href="{$path}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a>{if !$smarty.foreach.loop.last}, <br/>{/if}
						{/foreach}
					</span>
				</div>
				{/if}
					
				{if $subjects}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Subjects'}:</span>
					<span class="resultValue">
						{foreach from=$subjects item=subject name=loop}
							{foreach from=$subject item=subjectPart name=subloop}
								{if !$smarty.foreach.subloop.first} &gt; {/if}
								<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
							{/foreach}
							<br />
						{/foreach}
					</span>
				</div>
				{/if}
					
				{if $summary}
				<div class="resultInformation">
					<span class="resultLabel">{translate text='Summary'}:</span>
					<span class="resultValue">
						{$summary|escape}
					</span>
				</div>
				{/if}
			</div>	
			{* End Title *}
			<div id = "titleblock">
			
				{* Display series information *}
				{if $marcField|getvalue:'n' || $marcField|getvalue:'p'}
					<div class='titleSeriesInformation'>
					{if $marcField|getvalue:'n'}{$marcField|getvalue:'n'|regex_replace:"/(\/|:)$/":""|escape}{/if}
					{if $marcField|getvalue:'p'}{$marcField|getvalue:'p'|regex_replace:"/(\/|:)$/":""|escape}{/if}
					</div>
				{/if}
					
				{if $showTagging == 1}
					<div id="tagdetail">
						<table>
							<tr valign="top">
								<th>{translate text='Tags'}: </th>
								<td>
									<span style="float:right;">
										<a href="{$path}/Record/{$id|escape:"url"}/AddTag" class="tool add"
												onclick="GetAddTagForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add"}</a>
									</span>
									<div id="tagList">
										{if $tagList}
											{foreach from=$tagList item=tag name=tagLoop}
												<a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt}) 
												{if $tag->userAddedThis}
												<a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}&amp;resourceId={$id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from this title?");'>
													<img alt="Delete Tag" src="{$path}/images/silk/tag_blue_delete.png">
												</a>
												{/if} 
												{if !$smarty.foreach.tagLoop.last}, {/if}
											{/foreach}
										{else}
											{translate text='No Tags'}, {translate text='Be the first to tag this record'}!
										{/if}
									</div>
								</td>
							</tr>
						</table>
					</div>
				{/if}
			</div>
			<div>
				<div class="clearer">&nbsp;</div>
				<div id="seriesPlaceholder"></div>
				<div id="moredetails-tabs">
					{* Define tabs for the display *}
					<ul>
						<li><a href="#holdingstab">{translate text="Copies"}</a></li>
						{if $tableOfContents}
							<li><a href="#tableofcontentstab">{translate text="Contents"}</a></li>
						{/if}
						{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
							<li><a href="#prospectorTab">{translate text="In Prospector"}</a></li>
						{/if}
						{if $notes}
							<li><a href="#notestab">{translate text=$notesTabName}</a></li>
						{/if}
						{if $internetLinks && $show856LinksAsTab == 1}
							<li><a href="#linkstab">{translate text="Links"}</a></li>
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
					
					<div id = "holdingstab" >
						<a name = "holdings"></a>
						{if !$tabbedDetails}<div class = "blockhead">{translate text='Holdings'}</div>{/if}
						
						<div id="holdingsPlaceholder"></div>
						
						{if  $showProspectorTitlesAsTab == 0}
						<div id="prospectorHoldingsPlaceholder"></div>
						{/if}
					
						{if $internetLinks && $show856LinksAsTab == 0}
							<h3>{translate text="Internet"}</h3>
							{foreach from=$internetLinks item=internetLink}
								{if $proxy}
								<a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a><br/>
								{else}
								<a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a><br/>
								{/if}
							{/foreach}
						{/if}
						
					</div>
					
					{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 1}
						<div id="prospectorTab">
							{* Display in Prospector Sidebar *}
							<div id="inProspectorPlaceholder"></div>
						</div>
					{/if}
					
					{if $tableOfContents}
						<div id ="tableofcontentstab">
							<ul class='notesList'>
							{foreach from=$tableOfContents item=note}
								<li>{$note}</li>
							{/foreach}
							</ul>
						</div>
					{/if}
					
					{if $notes}
						<div id ="notestab">
							{if !$tabbedDetails}
							<div class = "blockhead">{translate text='Notes'}</div>
							{/if}
							<ul class='notesList'>
							{foreach from=$notes item=note}
								<li>{$note}</li>
							{/foreach}
							</ul>
						</div>
					{/if}
					
					{if $internetLinks && $show856LinksAsTab ==1}
						<div id ="linkstab">
							{foreach from=$internetLinks item=internetLink}
							{if $proxy}
							<a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a><br/>
							{else}
							<a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a><br/>
							{/if}
							{/foreach}
						</div>
					{/if}
		
					{if $showAmazonReviews || $showStandardReviews || $showComments}
						<div id="reviewtab">
							{if $showComments}
							<div id = "staffReviewtab" >
							{include file="$module/view-staff-reviews.tpl"}
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
					
					{if $tabbedDetails}
						<div id = "citetab">
							{if !$tabbedDetails}<div class = "blockhead">Citation </div>{/if}
							{include file="$module/cite.tpl"}
						</div>
					{/if}
								
					<div id = "stafftab">
						{include file=$staffDetails}
					</div>
				</div>
			</div>
		</div>
	</div>
		
	<script type="text/javascript">
	{literal}
	$(function() {
	$("#moredetails-tabs").tabs();
	});
	{/literal}
	</script> 
</div>
{/strip}