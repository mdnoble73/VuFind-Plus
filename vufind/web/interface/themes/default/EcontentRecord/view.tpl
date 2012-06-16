<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetEContentHoldingsInfo('{$id|escape:"url"}');
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
	  //alert("{$title}");
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
          
          {if $eContentRecord->publisher}
          <div class="sidebarLabel">{translate text='Publisher'}:</div>
            <div class="sidebarValue">{$eContentRecord->publisher|escape}</div>
          {/if}
          
          {if $eContentRecord->publishDate}
          <div class="sidebarLabel">{translate text='Published'}:</div>
            <div class="sidebarValue">{$eContentRecord->publishDate|escape}</div>
          {/if}
          
          <div class="sidebarLabel">{translate text='Format'}:</div>
          {if is_array($eContentRecord->format())}
           {foreach from=$eContentRecord->format() item=displayFormat name=loop}
             <div class="sidebarValue"><span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span></div>
           {/foreach}
          {else}
            <div class="sidebarValue"><span class="iconlabel {$eContentRecord->format()|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$eContentRecord->format}</span></div>
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
            <div class="sidebarValue"><a href="{$path}/Search/Results?lookfor=%22{$seriesListItem|escape:"url"}%22&amp;type=Series">{$seriesListItem|escape}</a></div>
          {/foreach}
          {/if} 
          
          {if count($topicList) > 0}
          <div class="sidebarLabel">{translate text='Topic'}:</div>
          {foreach from=$topicList item=topicListItem name=loop}
            <div class="sidebarValue">{$topicListItem|escape}</div>
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
        <a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=eContent" class="tool add"
           onclick="GetAddTagForm('{$id|escape}', 'eContent'); return false;">{translate text="Add Tag"}</a>
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
  </div> {* End sidebar *}
  
  <div id="main-content" class="full-result-content">
    <div id="record-header">
      {if isset($previousId)}
        <div id="previousRecordLink"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
      {/if}
      <div id="recordTitleAuthorGroup">
        {* Display Title *}
        <div id='recordTitle'>{$eContentRecord->title|regex_replace:"/(\/|:)$/":""|escape} 
        {if $user && $user->hasRole('epubAdmin')}
        {if $eContentRecord->status != 'active'}<span id="eContentStatus">({$eContentRecord->status})</span>{/if}
        <span id="editEContentLink"><a href='{$path}/EcontentRecord/{$id}/Edit'>(edit)</a></span>
        {if $eContentRecord->status != 'archived' && $eContentRecord->status != 'deleted'}
        	<span id="archiveEContentLink"><a href='{$path}/EcontentRecord/{$id}/Archive' onclick="return confirm('Are you sure you want to archive this record?  The record should not have any holds or checkouts when it is archived.')">(archive)</a></span>
        {/if}
        {if $eContentRecord->status != 'deleted'}
        	<span id="deleteEContentLink"><a href='{$path}/EcontentRecord/{$id}/Delete' onclick="return confirm('Are you sure you want to delete this record?  The record should not have any holds or checkouts when it is deleted.')">(delete)</a></span>
        {/if}
        {/if}
        </div>
        {* Display more information about the title*}
        {if $eContentRecord->author}
          <div class="recordAuthor">
            <span class="resultLabel">by</span>
            <span class="resultValue"><a href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></span>
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
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
	  </div>
	  
	  {* Checkout link *}
	  <div class='checkoutLink' id="checkout{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout"><img src="{$path}/interface/themes/default/images/checkout.png" alt="Checkout"/></a>
	  </div>
	  
	  {* Access online link *}
	  {*
	  <div class='accessOnlineLink' id="accessOnline{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/Home?detail=holdingstab#detailsTab"><img src="{$path}/interface/themes/default/images/access_online.png" alt="Access Online"/></a>
	  </div>
	  *}
	  
	  {* Add to Wish List *}
	  <div class='addToWishListLink' id="addToWishList{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/AddToWishList"><img src="{$path}/interface/themes/default/images/add_to_wishlist.png" alt="Add To Wish List"/></a>
	  </div>
	  
	  {if $showOtherEditionsPopup}
		<div id="otherEditionCopies">
			<div style="font-weight:bold"><a href="#" onclick="loadOtherEditionSummaries('{$id}', true)">{translate text="Other Formats and Languages"}</a></div>
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
             $('#myrating').rater({literal}{ {/literal} module:'EcontentRecord', rating:'{if $user}{$ratingData.user}{else}{$ratingData.average}{/if}', recordId: '{$id}', postHref: '{$path}/EcontentRecord/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
         {literal} } {/literal}
	    );
        </script>
      </div>
    </div> {* End image column *}
    
    <div id="record-details-column">
      <div id="record-details-header">
	      <div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">Loading...</div>
	      {if $enableProspectorIntegration == 1}
	      <div id="prospectorHoldingsPlaceholder"></div>
	      {/if}
	      <div id="recordTools">
		    <ul>
		      
		      {if !$tabbedDetails}
		        <li><a href="{$path}/EcontentRecord/{$id|escape:"url"}/Cite" class="cite" id="citeLink" onclick='ajaxLightbox("{$path}/EcontentRecord/{$id|escape}/Cite?lightbox", "#citeLink"); return false;'>{translate text="Cite this"}</a></li>
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
		    </ul>
		  </div>
		  
      	  <div class="clearer">&nbsp;</div>
	  </div>
      
      {if $eContentRecord->description}
      <div class="resultInformation">
        <div class="resultInformationLabel">{translate text='Description'}</div>
        <div class="recordDescription">
          {$eContentRecord->description|escape}
        </div>
      </div>
      {/if}
      
      {if count($subjectList) > 0}
      <div class="resultInformation">
        <div class="resultInformationLabel">{translate text='Subjects'}</div>
        <div class="recordSubjects">
          {foreach from=$subjectList item=subjectListItem name=loop}
              <a href="{$path}/Search/Results?lookfor=%22{$subjectListItem|escape:'url'}%22&amp;type=Subject">{$subjectListItem|escape}</a>
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
		
		<a id="detailsTabAnchor" name="detailsTab" href="#detailsTab"></a>
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
				{include file="Record/view-staff-reviews.tpl"}
				</div>
				 
				{if $showAmazonReviews || $showStandardReviews}
				<h4>Professional Reviews</h4>
				<div id='reviewPlaceholder'></div>
				{/if}
			</div>
      
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
      
      <div id = "holdingstab">
      	<div id="holdingsPlaceholder">Loading...</div>
      	{if $showOtherEditionsPopup}
				<div id="otherEditionCopies">
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
      
      {if $eContentRecord->marcRecord}
        <div id = "stafftab">
        	{include file=$staffDetails}
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
     