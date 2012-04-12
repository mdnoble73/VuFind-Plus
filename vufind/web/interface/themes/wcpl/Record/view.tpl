{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}
<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetHoldingsInfo('{$id|escape:"url"}');
	{if false && ($isbn || $upc)}
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
    <div id="titleDetailsAccordion">
      <h3 class='titleDetailsAccoridionHeader'><a href="#">{translate text="Title Details"}</a></h3>
      <div id="titleDetailsContents" class="sidegroupContents">
    	  {if $mainAuthor}
          <div class="sidebarLabel">{translate text='Main Author'}:</div>
          <div class="sidebarValue"><a href="{$path}/Author/Home?author={$mainAuthor|escape:"url"}">{$mainAuthor|escape}</a></div>
        {/if}
          
        {if $corporateAuthor}
          <div class="sidebarLabel">{translate text='Corporate Author'}:</div>
          <div class="sidebarValue"><a href="{$path}/Author/Home?author={$corporateAuthor|escape:"url"}">{$corporateAuthor|escape}</a></div>
        {/if}
          
        {if $contributors}
          <div class="sidebarLabel">{translate text='Contributors'}:</div>
          {foreach from=$contributors item=contributor name=loop}
            <div class="sidebarValue"><a href="{$path}/Author/Home?author={$contributor|escape:"url"}">{$contributor|escape}</a></div>
          {/foreach}
        {/if}
          
        {if $published}
          <div class="sidebarLabel">{translate text='Published'}:</div>
          {foreach from=$published item=publish name=loop}
            <div class="sidebarValue">{$publish|escape}</div>
          {/foreach}
        {/if}
          
        <div class="sidebarLabel">{translate text='Format'}:</div>
        {if is_array($recordFormat)}
          {foreach from=$recordFormat item=displayFormat name=loop}
            <div class="sidebarValue"><span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span></div>
          {/foreach}
        {else}
          <div class="sidebarValue"><span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span></div>
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
          
          <div id="ltfl_lexile"></div>
          
      </div>
      
      {if false && $showTagging == 1}
	    <h3 class='titleDetailsAccoridionHeader'><a href="#">{translate text="Tags"}</a></h3>
	    <div class="sidegroupContents">
	      {if $tagList}
	        {foreach from=$tagList item=tag name=tagLoop}
	          <div class="sidebarValue"><a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})</div>
	        {/foreach}
	      {else}
	        <div class="sidebarValue">{translate text='No Tags'}, {translate text='Be the first to tag this record'}!</div>
	      {/if}
	      <div class="sidebarValue">
	        <a href="{$path}/Record/{$id|escape:"url"}/AddTag" class="tool add"
	           onclick="GetAddTagForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add Tag"}</a>
	      </div>
	    </div>
	    {/if}
      
      <h3 id="ltfl_tagbrowse_button" style="display:none"><a href="#">{translate text="Tags"}</a></h3>
	    <div id="ltfl_tagbrowse" class="ltfl">
	    	<img src='{$path}/interface/themes/wcpl/images/loading_small.gif' alt='loading'/>
	    </div>
      
      <h3 id="ltfl_series_button" style="display:none"><a href="#">{translate text="Series"}</a></h3>
      <div id="ltfl_series" class="ltfl">
      	<img src='{$path}/interface/themes/wcpl/images/loading_small.gif' alt='loading'/>
      </div>
	    
      <h3 id="ltfl_awards_button" style="display:none"><a href="#">{translate text="Awards"}</a></h3>
      <div id="ltfl_awards" class="ltfl">
      	<img src='{$path}/interface/themes/wcpl/images/loading_small.gif' alt='loading'/>
      </div>
        
	    <h3 id="ltfl_similars_button" style="display:none"><a href="#">{translate text="Similar Books"}</a></h3>
	    <div id="ltfl_similars" class="ltfl">
	    	<img src='{$path}/interface/themes/wcpl/images/loading_small.gif' alt='loading'/>
	    </div>
	      
	    <h3 id="ltfl_related_button" style="display:none"><a href="#">{translate text="Related Editions"}</a></h3>
	    <div id="ltfl_related" class="ltfl">
	    	<img src='{$path}/interface/themes/wcpl/images/loading_small.gif' alt='loading'/>
	    </div>
      
      {* Display either similar tiles from novelist or from the catalog*}
      <div id="similarTitlePlaceholder" class="sidegroup" style='display:none'></div>
      {if is_array($similarRecords)}
        <h3><a href="#">{translate text="Other Titles"}</a></h3>
        <div class="sidegroupContents"> 
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
    
    <div id="similarAuthorPlaceholder" class="sidegroup" style='display:none'></div>
    
    {if is_array($editions)}
      <h3><a href="#">{translate text="Other Editions"}</a></h3>
      <div class="sidegroupContents">
        {foreach from=$editions item=edition}
          <div class="sidebarLabel">
            <a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
          </div>
          <div class="sidebarValue">
          {if is_array($edition.format)}
            <span class="{$edition.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">{$edition.format[0]}</span>
          {else}
            <span class="{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">{$edition.format}</span>
          {/if}
          {$edition.edition|escape}
          {if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
          </div>
        {/foreach}
      </div>
    {/if}
    
    </div>
    
  </div> {* End sidebar *}
  
  <div id="main-content" class="full-result-content">
    <div id="record-header">
      {if isset($previousId)}
        <div id="previousRecordLink"><a href="{$path}/Record/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/wcpl/images/prev.png" alt="Previous Record"/></a></div>
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
		      <div id="nextRecordLink"><a href="{$path}/Record/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."}{/if}"><img src="{$path}/interface/themes/wcpl/images/next.png" alt="Next Record"/></a></div>
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
      <div id = "recordcover">  
	    <div class="recordcoverWrapper">
          
          <a href="{$bookCoverUrl}">              
            <img alt="{translate text='Book Cover'}" class="recordcover" src="{$bookCoverUrl}" />
          </a>
        </div>
      </div>  
      

    
      {if $goldRushLink}
      <div class ="titledetails">
        <a href='{$goldRushLink}' >Check for online articles</a>
      </div>
      {/if}
          
    </div> {* End image column *}
    
    <div id="record-details-column">
      <div id="record-details-header">
	      <div id="holdingsSummaryPlaceholder" class="holdingsSummaryRecord">
	      	<img src='{$path}/interface/themes/wcpl/images/loading_small.gif' alt='loading'/>
	      </div>
	      

    
        <div id="actionTools">
          {* Place hold link *}
			    <div class='requestThisLink' id="placeHoldSummary{$id|escape:"url"}" style="display:none">
			      <a href="{$url}/Record/{$id|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
			    </div>
			    <div class='eBookLink' id="eBookLink{$id|escape:"url"}" style="display:none">
			    </div>
			    <div class='eAudioLink' id="eAudioLink{$id|escape:"url"}" style="display:none">
			    </div>
          <div id="saveLink{$id|escape}">
		        <a href="{$url}/Resource/Save?id={$id|escape:"url"}&amp;source=VuFind" style="padding-left:8px;" onclick="getSaveToListForm('{$id|escape}', 'VuFind'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
          </div>
          <div id="lists{$id|escape}"></div>
          <script type="text/javascript">
            getSaveStatuses('{$id|escape:"javascript"}');
          </script>
          <br />
              {if !empty($addThis)}
		        <li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></li>
          {/if}
          
          <br />
		  </div>
		  <div class="recordTools"> 
          {if !$tabbedDetails}
            <li><a href="{$url}/Record/{$id|escape:"url"}/Cite" class="cite" onclick='getLightbox("Record", "Cite", "{$id|escape}", null, "{translate text='Cite this'}"); return false;'>{translate text="Cite this"}</a></li>
          {/if}
          &nbsp;&nbsp;
          {if $showTextThis == 1}
            <a href="{$url}/Record/{$id|escape:"url"}/SMS" class="sms" onclick="getLightbox('Record', 'SMS', '{$id|escape}', null, '{translate text="Text this"}'); return false;">{translate text="Text this"}</a>
          {/if}
          &nbsp;&nbsp;
          {if $showEmailThis == 1}
            <a href="{$url}/Record/{$id|escape:"url"}/Email" class="mail" onclick="getLightbox('Record', 'Email', '{$id|escape}', null, '{translate text="Email this"}'); return false;" >{translate text="Email this"}</a>
          {/if}
          &nbsp;&nbsp;
          {if is_array($exportFormats) && count($exportFormats) > 0}
              <a href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onclick="toggleMenu('exportMenu'); return false;" >{translate text="Export Record"}</a><br />
              <ul class="menu" id="exportMenu">
                {foreach from=$exportFormats item=exportFormat}
                  <li><a {if $exportFormat=="RefWorks"} {/if}href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}" >{translate text="Export to"} {$exportFormat|escape}</a></li>
                {/foreach}
              </ul>
          {/if}
          &nbsp;&nbsp;
        </div>
        <div class="ltfl_reviews"></div>
        
      	<div class="clearer">&nbsp;</div>
	  </div>
      
      {if $summary}
      <div class="resultInformation">
      <br />
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
      
      {if $internetLinks}
      <div class="resultInformation">
      <br />
				<div class="resultInformationLabel">{translate text="Links"}</div>
				<div class="recordLinks">
				{foreach from=$internetLinks item=internetLink}
					{if $proxy}
					<a href="{$proxy}/login?url={$internetLink.link|escape:"url"}">{$internetLink.linkText|escape}</a><br/>
					{else}
					<a href="{$internetLink.link|escape}">{$internetLink.linkText|escape}</a><br/>
					{/if}
				{/foreach}
				</div>
			</div>
			{/if}
      
      {if $subjects}
      <div class="resultInformation">
      <br />
        <div class="resultInformationLabel">{translate text='Subjects'}</div>
        <div class="recordSubjects">
          {foreach from=$subjects item=subject name=loop}
            {foreach from=$subject item=subjectPart name=subloop}
              {if !$smarty.foreach.subloop.first} -- {/if}
              <a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;type=Subject">{$subjectPart.title|escape}</a>
            {/foreach}
            <br />
          {/foreach}
        </div>
      </div>
      {/if}
    </div>
   
    <div id="seriesList" style="display:none">
	    <div id="seriesHeader" class="titleScrollerHeader">
			<span class="seriesListTitle resultInformationLabel">Also in this series</span>
			<a href='{$path}/Record/{$id}/Series'><span class='seriesLink'>View as List</span></a>
		</div>
		<div id="titleScrollerSeries" class="titleScrollerBody">
			<div class="leftScrollerButton enabled" onclick="seriesScroller.scrollToLeft();"></div>
			<div class="rightScrollerButton" onclick="seriesScroller.scrollToRight();"></div>
			<div class="scrollerBodyContainer">
				<div class="scrollerBody" style="display:none">
				    
				</div>
				<div class="scrollerLoadingContainer">
					<img id="scrollerLoadingImageHome" class="scrollerLoading" src="{$path}/interface/themes/{$theme}/images/loading_large.gif" alt="Loading..." />
				</div>
			</div>
			<div class="clearer"></div>
			<div id="titleScrollerSelectedTitleSeries" class="titleScrollerSelectedTitle"></div>
			<div id="titleScrollerSelectedAuthorSeries" class="titleScrollerSelectedAuthor"></div>
		</div>    
	</div>
    
            
    {literal}
	<script type="text/javascript">
		$(function() {
			$("#moredetails-tabs").tabs();
			$("#moredetails-tabs").bind('tabsshow', function(event, ui) {
				setSidebarHeight();
			});
			$("#moredetails-tabs").bind('load', function(event, ui) {
				setSidebarHeight();
			});
			$("#titleDetailsAccordion").accordion();
		});
	</script>
	{/literal}
    
    <div id="moredetails-tabs">
      {* Define tabs for the display *}
      <ul>
        <li><a href="#holdingstab">Copies</a></li>
        {if $notes}
          <li><a href="#notestab">Notes</a></li>
        {/if}
        <li><a href="#citetab">Citation</a></li>
        <li><a href="#stafftab">Staff View</a></li>
      </ul>
      
      {* Display the content of individual tabs *}
      <div id = "holdingstab">
				
        <div id="holdingsPlaceholder">
        	<img src='{$path}/interface/themes/wcpl/images/loading_large.gif' alt='loading'/>
        </div>
        {* Place hold link *}
        <div class='requestThisLink' id="placeHoldHoldings{$id|escape:"url"}" style="display:none">
          <a href="{$url}/Record/{$id|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
        </div>
      </div>
      
      {if $notes}
        <div id ="notestab">
          <ul class='notesList'>
          {foreach from=$notes item=note}
            <li>{$note}</li>
          {/foreach}
          </ul>
        </div>
      {/if}
      
		  <div id = "citetab" >
        {include file="$module/cite.tpl"}
      </div>
      
      <div id = "stafftab">
        {include file=$staffDetails}
      </div>
      
      
    </div> {* End of tabs*}
            
  </div>
  
  {* Add COINS *}  
  <span class="Z3988" title="{$openURL|escape}"></span>
    
</div>
   
<script src="{$libraryThingUrl}/forlibraries/widget.js?id=1485-194868556" type="text/javascript"></script>
      