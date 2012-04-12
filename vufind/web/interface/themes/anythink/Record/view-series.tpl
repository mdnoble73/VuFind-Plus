<script type="text/javascript" src="{$path}/services/Search/ajax.js"></script>
<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>

{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
  alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
	  <div class="sidegroup">
	  	<h4>Series Information</h4>
	  	<div class="sidebarLabel">Series Name</div>
	  	<div class="sidebarValue">{$seriesTitle}</div>
	    <div class="sidebarLabel">Author</div>
	    {foreach from=$seriesAuthors item=author}
	    	<div class="sidebarValue">{$author}</div>
	    {/foreach}
      </div>
    </div>
   {* Eventually, we will put the series title here*}
   
   <div id="main-content">
   {* Listing Options *}
   <div id="searchInfo">
     {if $recordCount}
       {translate text="Showing"}
       <b>{$recordStart}</b> - <b>{$recordEnd}</b>
       {translate text='of'} <b>{$recordCount}</b>
     {/if}
   </div>
   {* End Listing Options *}
	
    {* Display series information *}
    <form id="addForm" action="{$path}/MyResearch/HoldMultiple">
    	<div id="seriesTitles">
		{foreach from=$recordSet item=record name="recordLoop"}
		    {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
			<div id="record{$record.recordId|escape}" class="result alt record{$smarty.foreach.recordLoop.iteration}">
			{else}
			<div id="record{$record.recordId|escape}" class="result record{$smarty.foreach.recordLoop.iteration}">
			{/if}
				<div class="selectTitle">
				  <input type="checkbox" name="selected[{$record.recordId|escape:"url"}]" id="selected{$record.recordId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('{$record.recordId|escape:"url"}', '{$record.title|regex_replace:"/(\/|:)$/":""|escape:"javascript"}', this);"{/if} />&nbsp;
				</div>
		    
		    	<div class="imageColumn"> 
				    <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
				    <a href="{$path}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
				    <img src="{$path}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
				    </a>
				    {* Place hold link *}
				    <div class='requestThisLink' id="placeHold{$record.recordId|escape:"url"}" style="display:none">
				      <a href="{$path}/Record/{$record.recordId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
				    </div>
				</div>
		  
		        <div class="resultDetails">
				  <div class="resultItemLine1">
					<a href="{$path}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$record.title|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$record.title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
					{if $record.title2}
				    <div class="searchResultSectionInfo">
				      {$record.title2|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
				    </div>
				    {/if}
				  </div>
				
				  <div class="resultItemLine2">
				    {if $record.author}
				      {translate text='by'}
				      {if is_array($record.author)}
				        {foreach from=$summAuthor item=author}
				          <a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
				        {/foreach}
				      {else}
				        <a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
				      {/if}
				    {/if}
				 
				    {if $record.publicationDate}{translate text='Published'} {$record.publicationDate|escape}{/if}
				  </div>
				
				  {if is_array($record.format)}
		            {foreach from=$record.format item=format}
		              <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
		            {/foreach}
		          {else}
		            <span class="iconlabel {$record.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$record.format}</span>
		          {/if}
				  <div id = "holdingsSummary{$record.recordId|escape:"url"}" class="holdingsSummary">
				    <div class="statusSummary" id="statusSummary{$record.recordId|escape:"url"}">
				      <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
				    </div>
				  </div>
				</div>
				
				<div id ="searchStars{$record.recordId|escape}" class="resultActions">
				  <div class="rate{$record.recordId|escape} stat">
					  <div class="statVal">
					    <span class="ui-rater">
					      <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
					      (<span class="ui-rater-rateCount-{$record.recordId|escape} ui-rater-rateCount">0</span>)
					    </span>
					  </div>
				      <div id="saveLink{$record.recordId|escape}">
				        {if $showFavorites == 1} 
				        <a href="{$path}/Resource/Save?id={$record.recordId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListForm('{$record.recordId|escape}', 'VuFind'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
				        {/if}
				        {if $user}
				        	<div id="lists{$record.recordId|escape}"></div>
							<script type="text/javascript">
							  getSaveStatuses('{$record.recordId|escape:"javascript"}');
							</script>
				        {/if}
				      </div>
				    </div>
            {assign var=id value=$record.recordId}
            {include file="Record/title-review.tpl"}
            
				    <script type="text/javascript">
				      $(
				         function() {literal} { {/literal}
				             $('.rate{$record.recordId|escape}').rater({literal}{ {/literal}recordId: {$record.recordId}, rating:0.0, postHref: '{$path}/Record/{$record.recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
				         {literal} } {/literal}
				      );
				    </script>
				      
				  </div>

				{if $record.recordId != -1}
				<script type="text/javascript">
				  addRatingId('{$record.recordId|escape:"javascript"}');
				  $(document).ready(function(){literal} { {/literal}
				  	addIdToStatusList('{$record.recordId|escape:"javascript"}');
				    resultDescription('{$record.recordId}','{$record.recordId}');
				  {literal} }); {/literal}
				</script>
				{/if}
			</div>
		{/foreach}
		{if !$enableBookCart}
		<input type="submit" name="placeHolds" value="Request Selected Items" class="requestSelectedItems"/>
		{/if}
		</div>
    </form>
	
	<script type="text/javascript">
	$(document).ready(function() {literal} { {/literal}
	   doGetRatings();
	   doGetSaveStatuses();
	   doGetStatusSummaries();
	{literal} }); {/literal}
	</script>
	
    {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
    </div>
</div>