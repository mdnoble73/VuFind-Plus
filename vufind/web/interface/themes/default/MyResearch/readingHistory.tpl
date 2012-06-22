{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<script type="text/javascript" src="{$path}/js/readingHistory.js" ></script>
<script type="text/javascript" src="{$path}/services/MyResearch/ajax.js" ></script>
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
      
					<div id='readingListDisclaimer' {if $historyActive == true}style='display: none'{/if}>
					The library takes seriously the privacy of your library records. Therefore, we do not keep track of what you borrow after you return it. 
					However, our automated system has a feature called "My Reading History" that allows you to track items you check out. 
					Participation in the feature is entirely voluntary. You may start or stop using it, as well as delete any or all entries in "My Reading History" at any time. 
					If you choose to start recording "My Reading History", you agree to allow our automated system to store this data. 
					The library staff does not have access to your "My Reading History", however, it is subject to all applicable local, state, and federal laws, and under those laws, could be examined by law enforcement authorities without your permission. 
					If this is of concern to you, you should not use the "My Reading History" feature.
					</div>
				</div>
          
				<div class="page">
					<form id='readingListForm' action ="{$fullPath}">
						<div>
							<input name='readingHistoryAction' id='readingHistoryAction' value='' type='hidden' />


							<div id="readingListActionsTop">
								{if $historyActive == true}
									{if $transList}
										<a class="button" onclick='return deletedMarkedAction()' href="#">Delete Marked</a>
										<a class="button" onclick='return deleteAllAction()' href="#">Delete All</a>
									{/if}
									<a class="button" onclick="return exportListAction();">Export To Excel</a>
									<a class="button" onclick="return optOutAction({if $transList}true{else}false{/if})" href="#">Stop Recording My Reading History</a>
								{else}
									<a class="button" onclick='return optInAction()' href="#">Start Recording My Reading History</a>
								{/if}
							</div>
							
							{if $transList}
							<div id="pager" class="pager">
								{if $pageLinks.all}<div class="myAccountPagination pagination">Page: {$pageLinks.all}</div>{/if}
								
								<span id="recordsPerPage">
								Records Per Page:
								<select id="pagesize" class="pagesize" onchange="changePageSize()">
									<option value="10" {if $recordsPerPage == 10}selected="selected"{/if}>10</option>
									<option value="25" {if $recordsPerPage == 25}selected="selected"{/if}>20</option>
									<option value="50" {if $recordsPerPage == 50}selected="selected"{/if}>30</option>
									<option value="75" {if $recordsPerPage == 75}selected="selected"{/if}>40</option>
									<option value="100" {if $recordsPerPage == 100}selected="selected"{/if}>50</option>
								</select>
								</span>
								
								<span id="sortOptions">
								Sort By:
								<select class="sortMethod" id="sortMethod" name="accountSort" onchange="changeAccountSort($(this).val())">
									{foreach from=$sortOptions item=sortOptionLabel key=sortOption}
										<option value="{$sortOption}" {if $sortOption == $defaultSortOption}selected="selected"{/if}>{$sortOptionLabel}</option>
									{/foreach}
								</select>
								</span>
								
								<div class='sortOptions'>
									Hide Covers <input type="checkbox" onclick="$('.imageColumn').toggle();"/>
								</div>
							</div>    
							{/if}
          {if $transList}
          
          <table class="myAccountTable" id="readingHistoryTable">
            <thead>
              <tr>
                <th><input id='selectAll' type='checkbox' onclick="toggleCheckboxes('.titleSelect', $(this).attr('checked'));" title="Select All/Deselect All"/></th>
                <th>{translate text='Title'}</th>
                <th>{translate text='Format'}</th>
                <th>{translate text='Out'}</th>
              </tr>
            </thead>
            <tbody> 
          
	          {foreach from=$transList item=record name="recordLoop" key=recordKey}
	          {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
							<tr id="record{$record.recordId|escape}" class="result alt record{$smarty.foreach.recordLoop.iteration}">
					{else}
							<tr id="record{$record.recordId|escape}" class="result record{$smarty.foreach.recordLoop.iteration}">
					{/if}
					<td class="titleSelectCheckedOut myAccountCell">
						<input type="checkbox" name="selected[{$record.recordId|escape:"url"}]" class="titleSelect" id="selected{$record.shortId|escape:"url"}" />
						</td>
						<td class="myAccountCell">
				    	{if $user->disableCoverArt != 1}
				    	<div class="imageColumn"> 
						    
						    <a href="{$url}/{if strcasecmp($record.source, 'vufind') == 0}Record{else}EcontentRecord{/if}/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
						    <img src="{$path}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
						    </a>
						    
						    <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>

						</div>
						{/if}
						    {* Place hold link *}
						    <div class='requestThisLink' id="placeHold{$record.recordId|escape:"url"}" style="display:none">
						      <a href="{$url}/{if strcasecmp($record.source, 'vufind') == 0}Record{else}EcontentRecord{/if}/{$record.recordId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
						    </div>				    
				      <div class="myAccountTitleDetails">
						  <div class="resultItemLine1">
							<a href="{$url}/{if strcasecmp($record.source, 'vufind') == 0}Record{else}EcontentRecord{/if}/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$record.title|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$record.title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
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
				       {$record.checkout|escape}{if $record.lastCheckout} to {$record.lastCheckout|escape}{/if}
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
          /*$("#readingHistoryTable")
          	.tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: { sorter: 'date' }, 4: { sorter: false }, 7: { sorter: false} } }{/literal})
            .tablesorterPager({literal}{container: $("#pager")}{/literal})
            	;*/
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