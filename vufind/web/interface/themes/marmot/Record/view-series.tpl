<script  type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script  type="text/javascript" src="{$path}/services/Search/ajax.js"></script>

{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
  alert("{$title}");
</script>
{/if}
<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
    <b class="btop"><b></b></b>

      {* Eventually, we will put the series title here*}
      {if isset($seriesTitle)}
      <h1>{$seriesTitle}</h1>
      All books in the <b>{$seriesTitle}</b> series.
      {else}
      <h1>Series Listing</h1>
      All books in this series.
      {/if}

      {* Listing Options *}
      <div class="yui-ge resulthead">
        <div class="yui-u first">
        {if $recordCount}
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {translate text='of'} <b>{$recordCount}</b>
        {/if}
        </div>

      </div>
      {* End Listing Options *}

      {* Display series information *}
      <form name="addForm" action="{$url}/MyResearch/HoldMultiple">
			{foreach from=$recordSet item=record name="recordLoop"}
			  {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
			  <div id="record{$record.recordId|escape}" class="result alt record{$smarty.foreach.recordLoop.iteration}">
			  {else}
			  <div id="record{$record.recordId|escape}" class="result record{$smarty.foreach.recordLoop.iteration}">
			  {/if}
			  <div class="selectTitle">
			    <input type="checkbox" name="selected[{$record.shortId|escape:"url"}]" id="selected{$record.shortId|escape:"url"}" style="display:none" />&nbsp;
			  </div>
			        
			   <div class="yui-u first resultsList">
			       {if $record.recordId != -1}
			        <div id = "holdingsSummary{$record.shortId|escape:"url"}" class="holdingsSummary"  style="width:200px">
			            <div class="statusSummary" id="statusSummary{$record.shortId|escape:"url"}">
			              <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
			            </div>
			            
			            <div style='display:none' id="placeHold{$record.shortId|escape:"url"}"><a href="{$url}/Record/{$record.id|escape:"url"}/Hold" class="hold">{translate text = 'Request This Title'}&nbsp; &nbsp;</a></div>
			            <div style="display:none" id="callNumber{$record.shortId|escape:"url"}">Call Number: {$holdingsSummaryCallnumber} </div>
			            <div style="display:none" id="downloadLink{$record.shortId|escape:"url"}"></div>
			            <div style ="display:none;color:#646464;font-size:8pt;" id="copyInfo{$record.shortId|escape:"url"}">{$holdingsSummaryavailablecopies} of {$holdingsSummarynumcopies} Copies available</div>
			        </div>
			       {/if}
			    <div class="yui-ge">
			        
			        {if $record.isbn || $record.upc}
			        <img src="{$path}/bookcover.php?isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}{if isset($record.format_category) && is_array($record.format_category)}&amp;category={$record.format_category.0|escape:"url"}{/if}" class="alignleft" alt="{translate text='Cover Image'}"/>
			        {elseif isset($record.format_category)}
			        <img src="{$path}/interface/themes/marmot/images/{$record.format_category.0|escape:"url"}.png" class="alignleft" alt="{translate text='No Cover Image'}"/><br />
			        {/if}
			        <div class="resultitem">
			          <div class="resultItemLine1">
			          <a href="{$url}/Record/{$record.id|escape:"url"}?searchId={$searchId}&recordIndex={$smarty.foreach.recordLoop.iteration+$recordStart-1}&page={$page}" class="title">{if !$record.title}{translate text='Title not available'}{else}{$record.title|truncate:180:"..."|highlight:$lookfor}{/if}</a>
			          {if $record.title2}
			          <br />
			          {$record.title2|truncate:180:"..."|highlight:$lookfor}
			          {/if}
			          
			         {if $record.recordId != -1 && $showRatings == 1}
				         {* Let the user rate this title *}
	               {include file="Record/title-rating.tpl" ratingClass="searchStars" recordId=$record.id shortId=$record.shortId}
			         {/if}
			          </div>
			  
			          <div class="resultItemLine2">
			          {if $record.author}
			          {translate text='by'}
			          {if is_array($record.author)}
			            {foreach from=$record.author item=author}
			          <a href="{$url}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
			            {/foreach}
			          {else}
			          <a href="{$url}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
			          {/if}
			          {/if}
			    
			          {if $record.publicationDate}{translate text='Published'} {$record.publicationDate|escape}{/if}
			          </div>
			
			          <div class="resultItemLine3">
			           
			          {* If we have an ISSN and an OpenURL resolver, use those to provide full
			             text.  Otherwise, check to see if there was a URL stored in the Solr
			             record and assume that is full text instead. *}
			          {if $record.issn && $openUrlLink}
			            {if is_array($record.issn)}
			              {assign var='currentIssn' value=$record.issn.0|escape:"url"}
			            {else}
			              {assign var='currentIssn' value=$record.issn|escape:"url"}
			            {/if}
			            {assign var='extraParams' value="issn=`$currentIssn`&genre=journal"}
			            <br /><a href="{$openUrlLink|addURLParams:"`$extraParams`"|escape}" class="fulltext"
			              onclick="window.open('{$openUrlLink|addURLParams:"`$extraParams`"|escape}', 'openurl', 'toolbar=no,location=no,directories=no,buttons=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=550,height=600'); return false;">{translate text='Get full text'}</a>
			          {elseif $record.url}
			             {* Remove download links for now since we are pulling them from item records 
			             {if is_array($record.url)}
			               {foreach from=$record.url item=recordurl}
			                 <br /><a href="{$recordurl|escape}" class="fulltext" target="_new"><img height="" width="" src={$url}/interface/themes/marmot/images/download.jpg></a>
			               {/foreach}
			             {else}
			 <br /><a href="{$recordurl|escape}" class="fulltext" target="_new"><img src={$url}/interface/themes/marmot/images/download.jpg></a>
			              {/if}
			              *}
			          {else}
			            
			          {/if}
			          </div>
			          
			          {if is_array($record.format)}
			            {foreach from=$record.format item=format}
			              <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
			            {/foreach}
			          {else}
			            <span class="iconlabel {$record.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$record.format}</span>
			          {/if}
			        </div>
			      </div>
			   
			      
			     </div>
			
			    {* Clear floats so the record displays as a block*}
			    <div class='clearer'></div>
			  </div>
			
			  {if $record.recordId != -1}
			  <script type="text/javascript">
			    getStatusSummary('{$record.id|escape:"javascript"}');
			  </script>
			  {/if}
			
			{/foreach}
			{if !$enableBookCart}
			<input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
			{/if}
			</form>
			
			<script type="text/javascript">
			$(document).ready(function() {literal} { {/literal}
		    doGetRatings();
			  doGetSaveStatuses();
			{literal} }); {/literal}
			</script>
      

      {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
      <b class="bbot"><b></b></b>
    </div>
    {* End Main Listing *}
    
  </div>

  {* Narrow Search Options *}
  <div class="yui-b">
    {* Nothing to show in the sidebar for now.  Perhaps we will be able to facet that data later. *}
  </div>
  {* End Narrow Search Options *}

</div>
