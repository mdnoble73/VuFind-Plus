<script  type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<script  type="text/javascript" src="{$path}/js/jcarousel/lib/jquery.jcarousel.min.js"></script>
<script type="text/javascript" src="{$path}/js/dropdowncontent.js"></script>

{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}

<script type="text/javascript">
{literal}$(document).ready(function(){{/literal}
	GetHoldingsInfoMSC('{$id|escape:"url"}');
	{if $isbn || $upc}
		GetEnrichmentInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}', '{$upc|escape:"url"}', {$showSeriesAsTab});
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
	{if $showComments == 1}
		dropdowncontent.init("userreviewlink", "left-bottom", 150, 'click')
	{/if}
	{if (isset($title)) }
		alert("{$title}");
	{/if}
{literal}});{/literal}

function redrawSaveStatus() {literal}{{/literal}
	getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
{literal}}{/literal}
</script>

<div id="bd">
	<div class="toolbar">
	<ul>
		{if isset($previousId)}
			<li><a href="{$url}/Record/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" class="previousLink" title="{if !$previousTitle}{translate text='Title not available'}{else}{$previousTitle|truncate:180:"..."}{/if}">{translate text="Previous"}</a></li>
		{/if}
		{if !$tabbedDetails}
			<li><a href="{$url}/Record/{$id|escape:"url"}/Cite" class="cite" onclick="getLightbox('Record', 'Cite', '{$id|escape}', null, '{translate text="Cite this"}'); return false;">{translate text="Cite this"}</a></li>
		{/if}
		{if $showTextThis == 1}
			<li><a href="{$url}/Record/{$id|escape:"url"}/SMS" class="sms" onclick="getLightbox('Record', 'SMS', '{$id|escape}', null, '{translate text="Text this"}'); return false;">{translate text="Text this"}</a></li>
		{/if}
		{if $showEmailThis == 1}
			<li><a href="{$url}/Record/{$id|escape:"url"}/Email" class="mail" onclick="getLightbox('Record', 'Email', '{$id|escape}', null, '{translate text="Email this"}'); return false;">{translate text="Email this"}</a></li>
		{/if}
		{if is_array($exportFormats) && count($exportFormats) > 0}
			<li>
				<a href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onclick="toggleMenu('exportMenu'); return false;">{translate text="Export Record"}</a><br />
				<ul class="menu" id="exportMenu">
				{foreach from=$exportFormats item=exportFormat}
					<li><a {if $exportFormat=="RefWorks"}target="{$exportFormat}Main" {/if}href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
				{/foreach}
				</ul>
			</li>
		{/if}
		{if $showFavorites == 1}
			<li id="saveLink"><a href="{$url}/Resource/Save?id={$id|escape:"url"}&amp;source=VuFind" class="fav" onclick="getSaveToListForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add to favorites"}</a></li>
		{/if}
		{if !empty($addThis)}
			<li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></li>
		{/if}
		<li id="Holdings"><a href="#holdings" class ="holdings">{translate text="Holdings"}</a></li>
		{if isset($nextId)}
			<li><a href="{$url}/Record/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" class="nextLink" title="{if !$nextTitle}{translate text='Title not available'}{else}{$nextTitle|truncate:180:"..."}{/if}">{translate text="Next"}</a></li>
		{/if}
	</ul>
	</div>
  {if $error}<p class="error">{$error}</p>{/if} 
       
  <div id="main_content_with_sidebar" class="content">
    <div id = "fullcontent">
        <div id='fullRecordSummaryAndImage'>
	      	<div class="clearer"></div>
	        {* Display Book Cover *}
	        {if $isbn || $upc}
	            
	                <div class="recordcoverWrapper">
			            <a href="{$path}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=large&amp;upc={$upc}&amp;category={$format_category|escape:"url"}&amp;format={$recordFormat.0|escape:"url"}">              
		                    <img alt="{translate text='Book Cover'}" class="recordcover" src="{$path}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=medium&amp;upc={$upc}&amp;category={$format_category|escape:"url"}&amp;format={$recordFormat.0|escape:"url"}" >
		                </a>
	                    <div id="goDeeperLink" class="godeeper" style="display:none">
	                      <a href="{$url}/Record/{$id|escape:"url"}/GoDeeper" onclick="getLightbox('Record', 'GoDeeper', '{$id|escape}', null, '{translate text="Go Deeper"}', undefined, undefined, undefined, '5%', '90%', 50, '85%'); return false;">
	                      <img alt="{translate text='Go Deeper'}" src="{$path}/images/deeper.png"></a>
	                    </div>
	                </div>
		      {/if}
		      <div class='requestThisLink' style='display:none'>
	        	<a href="{$url}/Record/{$id|escape:"url"}/Hold" class="holdRequest" style="display:inline-block;font-size:11pt;">{translate text="Request This"}</a><br />
	        </div>
		      
		      {if $showRatings == 1}
				  <div id="ratingSummary">
				    <span class="ratingHead">Patron Rating</span><br /><br />
				    <div id="rate{$noDot}" class="stat">
				    <div class="statVal">
				      <span class="ui-rater">
				        <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
				        <span class="ui-rater-rating">{$ratingData.average|string_format:"%.2f"}</span>&#160;(<span class="ui-rater-rateCount">{$ratingData.count}</span>)
				      </span>
				        </div>
				        <script type="text/javascript">
				        $(
				          function() {literal} { {/literal}
				              $('#rate{$noDot}').rater({literal}{ {/literal} rating:'{$ratingData.average}', postHref: '{$url}/Record/{$id}/AJAX?method=RateTitle'{literal} } {/literal});
				          {literal} } {/literal}
				        );
				        </script>
				          </div>
				     <center><span class="smallText">Average Patron Rating</span></center><br />
				       {$ratingData.count} ratings<br />
				    
				    <img src="{$url}/{$ratingData.summaryGraph}" alt='Ratings Summary'> 
				  
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
            <span class="resultValue"><a href="{$url}/Author/Home?author={$mainAuthor|escape:"url"}">{$mainAuthor|escape}</a></span>
          </div>
          {/if}
          
          
          
          {if $corporateAuthor}
          <div class="resultInformation">
            <span class="resultLabel">{translate text='Corporate Author'}:</span>
            <span class="resultValue">{$corporateAuthor|escape}</span>
          </div>
          {/if}
          
          <div class="resultInformation">
            <span class="resultLabel">&nbsp;</span>
            <span class="resultValue" id ="similarAuthorPlaceholder"></span>
          </div>
          
          {assign var=marcField value=$marc->getField('240')}
            {if $marcField}
            <div class="resultInformation">
            	<span class="resultLabel">{translate text='Uniform Title:'}</span>
            	<span class="resultValue"><a href="{$url}/Union/Search?searchSource=local&lookfor={$marcField|getvalue:'a'|escape:"url"}{if $marcField|getvalue:'m'}{$marcField|getvalue:'m'|escape:"url"}{/if}{if $marcField|getvalue:'n'}{$marcField|getvalue:'n'|escape:"url"}{/if}{if $marcField|getvalue:'o'}{$marcField|getvalue:'o'|escape:"url"}{/if}">{$marcField|getvalue:'a'}{if $marcField|getvalue:'m'}{$marcField|getvalue:'m'}{/if}{if $marcField|getvalue:'n'}{$marcField|getvalue:'n'}{/if}{if $marcField|getvalue:'o'}{$marcField|getvalue:'o'}{/if}</a></span>
        	</div>
        	{/if}
          {if $contributors}
          <div class="resultInformation">
            <span class="resultLabel">{translate text='Contributors'}:</span>
            <span class="resultValue">
              {foreach from=$contributors item=contributor name=loop}
                <a href="{$url}/Author/Home?author={$contributor|escape:"url"}">{$contributor|escape}</a>{if !$smarty.foreach.loop.last}, <br/>{/if}
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
          
					<div class="resultInformation" ><span class="resultLabel">{translate text='Call Number'}:</span><span class="resultValue boldedResultValue" id="callNumberValue">Loading...</span></div>
					<div class="resultInformation" ><span class="resultLabel">{translate text='Location'}:</span><span class="resultValue boldedResultValue" id="locationValue">Loading...</span></div>
					<div class="resultInformation" id="downloadLink" style="display:none"><span class="resultLabel">{translate text='Download From'}:</span><span class="resultValue" id="downloadLinkValue">Loading...</span></div>
					<div class="resultInformation" ><span class="resultLabel">{translate text='Status'}:</span><span class="resultValue" id="statusValue">Loading...</span></div>
          
          
          {if $series}
          <div class="resultInformation">
            <span class="resultLabel">{translate text='Series'}:</span>
            <span class="resultValue">
              {foreach from=$series item=seriesItem name=loop}
                <a href="{$url}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a>{if !$smarty.foreach.loop.last}, <br/>{/if}
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
                  <a href="{$url}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;type=Subject">{$subjectPart.title|escape}</a>
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
                  <a href="{$url}/Record/{$id|escape:"url"}/AddTag" class="tool add"
                     onclick="GetAddTagForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add"}</a>
                </span>
                <div id="tagList">
                  {if $tagList}
                    {foreach from=$tagList item=tag name=tagLoop}
                      <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt}) 
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
            <div id = "readertab">
              {if !$tabbedDetails}
	            <div class = "blockhead">Reader Reviews 
	            <span style ="font-size:12px;" class ="alignright"><a href="#" id="userreviewlink" class="add" rel="userreview">Add a Review</a></span></div>
	            {else}
	            <div style ="font-size:12px;" class ="alignright"><a href="#" id="userreviewlink" class="add" rel="userreview">Add a Review</a></div>
              {/if}
	            <DIV id="userreview" style="position:absolute; -moz-border-radius: 5px; -webkit-border-radius: 5px; -webkit-box-shadow: 5px 5px 7px 0 #888; padding: 5px; -moz-box-shadow: 5px 5px 7px 0 #888; visibility: hidden; border: 2px solid darkgrey; background-color: white; width: 400px; height:150px;">
	            <span class ="alignright"><a href="javascript:dropdowncontent.hidediv('userreview')" class="unavailable">Close</a></span><br />
	            Add your Review <br />
	       
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
            
            <div id = "holdingstab" >
	            <a name = "holdings"></a>
	            {if !$tabbedDetails}<div class = "blockhead">{translate text='Holdings'}</div>{/if}
            
              {assign var=marcField value=$marc->getFields('856')}
							{if $marcField}
							<h3>{translate text="Internet"}</h3>
							{foreach from=$marcField item=field name=loop}
							{if $proxy}
              <a href="{$proxy}/login?url={$field|getvalue:'u'|escape:"url"}">{if $field|getvalue:'3'}{$field|getvalue:'3'|escape}{elseif $field|getvalue:'z'}{$field|getvalue:'z'|escape}{else}{$field|getvalue:'u'|escape}{/if}</a><br/>
              {else}
              <a href="{$field|getvalue:'u'|escape}">{if $field|getvalue:'3'}{$field|getvalue:'3'|escape}{elseif $field|getvalue:'z'}{$field|getvalue:'z'|escape}{else}{$field|getvalue:'u'|escape}{/if}</a><br/>
              {/if}
							{/foreach}
							{/if}
              <div id="holdingsPlaceholder"></div>
              <div id="prospectorHoldingsPlaceholder"></div>
            </div>
            
            <div id = "stafftab">
              {include file=$staffDetails}
            </div>
         </div>
       </div>     
   </div>
   
   <div id = "classicViewLink"><a href ="{$classicUrl}/record={$classicId|escape:"url"}" target="_blank">Classic View</a></div>
   {if $linkToAmazon == 1 && $isbn}
   <div class="titledetails">
        <a href="http://amazon.com/dp/{$isbn|@formatISBN}" target="_blank" class='amazonLink'> {translate text = "View on Amazon"}</a>
   </div>
   {/if}
       
   </div>
   
<script type="text/javascript">
{literal}
$(function() {
$("#moredetails-tabs").tabs();
});
{/literal}
</script> 
   
    <div id='similarTitles' class="left_sidebar" style='display:none'>
     {* Display either similar tiles from novelist or from the catalog*}
     <div id="similarTitlePlaceholder" style='display:none'></div>
     
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
          <a href="{$url}/Record/{$similar.id|escape:"url"}">{$similar.title|regex_replace:"/(\/|:)$/":""|escape}</a>
          </span>
          <span style="font-size: 80%">
          {if $similar.author}<br />{translate text='By'}: {$similar.author|escape}{/if}
          </span>
        </li>
        {/foreach}
      </ul>
     </div>
     {/if}
    </div>
    
    
    <div class="left_sidebar">
    {* Display in Prospector Sidebar *}
    <div id="inProspectorPlaceholder"></div>
    </div>
    
    {if is_array($editions)}
    <div class="left_sidebar">
      <h4>{translate text="Other Editions"}</h4>
      <ul class="similar">
        {foreach from=$editions item=edition}
        <li>
          {if is_array($edition.format)}
            <span class="{$edition.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
          {else}
            <span class="{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">
          {/if}
          <a href="{$url}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
          </span>
          {$edition.edition|escape}
          {if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
        </li>
        {/foreach}
      </ul>
    </div>
    {/if}
  </div>