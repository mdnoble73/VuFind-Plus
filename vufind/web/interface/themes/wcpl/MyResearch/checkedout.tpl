{if (isset($title)) }
<script type="text/javascript">
    alert("{$title}");
</script>
{/if}
<script type="text/javascript" src="{$path}/js/holds.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    {if $user->cat_username}
          
      <div class="myAccountTitle">{translate text='Your Checked Out Items'}</div>
      {if $userNoticeFile}
        {include file=$userNoticeFile}
      {/if}
      
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
	      <form id="renewForm" action="{$path}/MyResearch/RenewMultiple">
          <div>
            <a href="#" onclick="return renewSelectedTitles();" class="button">Renew Selected Items</a>
            <a href="{$path}/MyResearch/RenewAll" class="button">Renew All</a>
          </div>
            
          <div class='clearer'></div>
          <table class="myAccountTable" id="checkedOutTable">
            <thead>
              <tr>
                <th><input id='selectAll' type='checkbox' onclick="$('.titleSelect').attr('checked', $('#selectAll').attr('checked'));" title="Select All/Deselect All"/></th>
                <th>{translate text='Title'}</th>
                <th>{translate text='Format'}</th>
                <th>{translate text='Out'}</th>
                <th>{translate text='Due'}</th>
                <th>{translate text='Renewed'}</th>
                <th>{translate text='Wait List'}</th>
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
						  <input type="checkbox" name="selected[{$record.itemid|escape:"url"}|{$record.itemindex}]" class="titleSelect" id="selected{$record.itemid|escape:"url"}" />
						</td>
				    
            <td class="myAccountCell">
				    	<div class="imageColumn"> 
						    <div id='descriptionPlaceholder{$record.recordId|escape}' style='display:none'></div>
						    <a href="{$url}/Record/{$record.recordId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$record.recordId|escape:"url"}">
						    <img src="{$path}/bookcover.php?id={$record.recordId}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
						    </a>
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
				       {$record.checkoutdate|date_format}
		        </td>            
		        <td class="myAccountCell">
		          {$record.duedate|date_format}
              {if $record.overdue}
                <span class='overdueLabel'>OVERDUE</span>
              {elseif $record.daysUntilDue == 0}
                <span class='dueSoonLabel'>(Due today)</span>
              {elseif $record.daysUntilDue == 1}
                <span class='dueSoonLabel'>(Due tomorrow)</span>
              {elseif $record.daysUntilDue <= 7}
                <span class='dueSoonLabel'>(Due in {$record.daysUntilDue} days)</span>
              {/if}
            </td>  
		                    
		        <td class="myAccountCell">
		          {$record.renewCount}
              {if $record.renewMessage}
                <div class='{if $record.renewResult == true}renewPassed{else}renewFailed{/if}'>
                  {$record.renewMessage|escape}
                </div>
              {/if}
            </td>
            
            <td class="myAccountCell">
              {* Wait List goes here *}
              {$record.holdQueueLength}
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
      
		    <div>
		      <a href="#" onclick="return renewSelectedTitles();" class="button">Renew Selected Items</a>
		      <a href="{$path}/MyResearch/RenewAll" class="button">Renew All</a>
		    </div>
		  </form>
      
      <script type="text/javascript">
        $(document).ready(function() {literal} { {/literal}
          doGetRatings();
          $("#checkedOutTable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: { sorter: 'date' }, 4: { sorter: 'date' }, 7: { sorter: false} } }{/literal});
        {literal} }); {/literal}
      </script>
    {else}
	    {translate text='You do not have any items checked out'}.
    {/if}
  {else}
    You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
  {/if}
  </div>
</div>