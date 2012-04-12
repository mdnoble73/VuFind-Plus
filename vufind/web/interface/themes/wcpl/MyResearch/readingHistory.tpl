{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<script type="text/javascript" src="{$path}/js/readingHistory.js" ></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
        {if $user->cat_username}
        <div class="resulthead">
          <div class="myAccountTitle">{translate text='My Reading History'} {if $historyActive == true}<span id='readingListWhatsThis' onclick="$('#readingListDisclaimer').toggle();">(What's This?)</span>{/if}</div>
          {if $userNoticeFile}
            {include file=$userNoticeFile}
          {/if}
      
          <div id='readingListDisclaimer' {if $historyActive == true}style='display: none'{/if}>The  library takes seriously the privacy of your library records. Therefore, we do  not keep track of what you borrow after you return it. However, our automated  system has a feature called &quot;My Reading History&quot; that allows you to  track items you check out. Participation in the feature is entirely voluntary.  You may start or stop using it, as well as delete any or all entries in  &quot;My Reading History&quot; at any time. If you choose to start recording  &quot;My Reading History&quot;, you agree to allow our automated system to store  this data. &quot;My Reading History&quot; is subject to all applicable local,  state, and federal laws, and under those laws, could be examined by law  enforcement authorities. If this is of concern to you, you should not use the  &quot;My Reading History&quot; feature.</div>
          </div>
          
          <div class="page">
          <form id='readingListForm' action ="{$fullPath}">
          <div>
          <input name='readingHistoryAction' id='readingHistoryAction' value='' type='hidden' />
          {if $transList}
           <div class='sortOptions'>
          {*
          {translate text='Sort by'}
          <select name="sort" id="sort" onchange="changeSort($(this).val());">
          {foreach from=$sortOptions item=sortDesc key=sortVal}
            <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
          {/foreach}
          </select> *}
          {translate text='Hide Covers'} <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
        </div>
        
          {/if}
          <div id="readingListActionsTop">
            {if $historyActive == true}
              {if $transList}
                <a class="button" onclick='return deletedMarkedAction()' href="#">Delete Marked</a>
		            <a class="button" onclick='return deleteAllAction()' href="#">Delete All</a>
	            {/if}
	            {* <button value="exportList" class="RLexportList" onclick='return exportListAction()'>Export Reading History</button> *}
              <a class="button" onclick='return optOutAction({if $transList}true{else}false{/if})' href="#">Stop Recording My Reading History</a>
	          {else}
	            <a class="button" onclick='return optInAction()' href="#">Start Recording My Reading History</a>
	          {/if}
          </div>

          {if $transList}
          
          <table class="myAccountTable" id="readingHistoryTable">
            <thead>
              <tr>
                <th><input id='selectAll' type='checkbox' onclick="$('.titleSelect').attr('checked', $('#selectAll').attr('checked'));" title="Select All/Deselect All"/></th>
                <th>{translate text='Title'}</th>
                <th>{translate text='Format'}</th>
                <th>{translate text='Out'}</th>
              </tr>
            </thead>
            <tbody> 
          
	          {foreach from=$transList item=record name="recordLoop"}
				    {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
							<tr id="record{$record.recordId|escape}" class="result alt record{$smarty.foreach.recordLoop.iteration}">
					{else}
							<tr id="record{$record.recordId|escape}" class="result record{$smarty.foreach.recordLoop.iteration}">
					{/if}
					<td class="titleSelectCheckedOut myAccountCell">
						<input type="checkbox" name="selected[{$record.recordId|escape:"url"}]" class="titleSelect" id="selected{$record.recordId|escape:"url"}" />
						</td>
						<td class="myAccountCell">
				    	<div class="imageColumn"> 
						    <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
						    <a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
						    <img src="{$path}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
						    </a>
						    {* Place hold link *}
						    <div class='requestThisLink' id="placeHold{$record.recordId|escape:"url"}" style="display:none">
						      <a href="{$url}/Record/{$record.recordId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
						    </div>
						</div>
				    
				      <div class="myAccountTitleDetails">
						  <div class="resultItemLine1">
							<a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$record.title|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$record.title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
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
						          <a href="{$url}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
						        {/foreach}
						      {else}
						        <a href="{$url}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
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
				          
               </div>
            </td>
						
			      <td class="myAccountCell">
              {if is_array($record.format)}
                {foreach from=$record.format item=format}
                  {translate text=$format}
                {/foreach}
              {else}
                {translate text=$record.format}
              {/if}
            </td>
            
            <td class="myAccountCell">      
				       {$record.checkout|escape} to {$record.lastCheckout|escape}
		        </td>             
            

						
						{if $record.recordId != -1}
						<script type="text/javascript">
						  $(document).ready(function(){literal} { {/literal}
						      resultDescription('{$record.recordId}','{$record.recordId}');
						  {literal} }); {/literal}
						</script>
						{/if}
					</tr>
				{/foreach}
	        </tbody>
      </table>           
	          
				<script type="text/javascript">
        $(document).ready(function() {literal} { {/literal}
          doGetRatings();
          $("#readingHistoryTable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: { sorter: 'date' }, 4: { sorter: false }, 7: { sorter: false} } }{/literal});
        {literal} }); {/literal}
      </script>
          {else if $historyActive == true}
            {* No Items in the history, but the history is active *}
            You do not have any items in your reading list.  It may take up to 3 hours for your reading history to be updated after you start recording your history.
          {/if}
          {if $transList} {* Don't double the actions if we don't have any items *}
	          <div id="readingListActionsBottom">
	            {if $historyActive == true}
	              {if $transList}
	                <a class="button" onclick="return deletedMarkedAction()" href="#">Delete Marked</a>
                  <a class="button" onclick="return deleteAllAction()" href="#">Delete All</a>
	              {/if}
	              {* <button value="exportList" class="RLexportList" onclick='return exportListAction()'>Export Reading History</button> *}
                <a class="button" onclick='return optOutAction({if $transList}true{else}false{/if})' href="#">Stop Recording My Reading History</a>
	            {else}
	              <a class="button" onclick='return optInAction()' href="#">Start Recording My Reading History</a>
	            {/if}
	          </div>
          {/if}
          </div>
          </form>
          </div>
        {else}
          <div class="page">
            You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
          </div>
        {/if}
	</div>
</div>


