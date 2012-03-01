<script type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/holds.js"></script>
{if (isset($title)) }
<script type="text/javascript">
    alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
        {if $user->cat_username}
          <div class="resulthead">
          <h3>{translate text='Your Checked Out Items'}</h3>
          </div>
          <div class="page">
          {if $transList}
            <div class='sortOptions'>
              {translate text='Sort'}
              <select name="sort" id="sort" onchange="changeSort($(this).val());">
              {foreach from=$sortOptions item=sortDesc key=sortVal}
                <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected{/if}>{translate text=$sortDesc}</option>
              {/foreach}
              </select>
            </div>
	          {if $oneOrMoreRenewableItems}
		          <form name="addForm" action="{$url}/MyResearch/RenewMultiple">
		          <div class='selectAllControls'>
		            <a href="#" onclick="$('.titleSelect').attr('checked', true);return false;">Select All</a> /
		            <a href="#" onclick="$('.titleSelect').attr('checked', false);return false;"/>Deselect All</a>
		          </div>
	          {/if}
	          <div class='clearer'></div>
	          <ul class="filters">
	          {foreach from=$transList item=resource name="recordLoop"}
	            {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
	            <li class="result alt">
	            {else}
	            <li class="result">
	            {/if}
	              <div class="yui-ge">
	                <div >
	                  
		                <div class="selectTitle">
										  <input type="checkbox" name="selected[{$resource.itemid|escape:"url"}|{$resource.itemindex}]" class="titleSelect" id="selected{$resource.itemid|escape:"url"}" {if !$resource.canrenew}style="display:none"{/if} />&nbsp;
									  </div>
	                  <img src="{$path}/bookcover.php?isn={$resource.isbn|@formatISBN}&amp;size=small&amp;upc={$resource.upc|escape:"url"}&amp;category={$resource.category|escape:"url"}" alt="bookcover" class="alignleft">
	                  
	                  {if $resource.canrenew}
	                    <div class="alignright"> <a href = "{$url}/MyResearch/Renew?itemId={$resource.itemid}&amp;itemIndex={$resource.itemindex}" class="renew">{translate text='Renew Item'}</a></div>
	                  {/if}
	                  {if $resource.id != null}
	                    {* Let the user rate this title *}
	                    {if $showRatings == 1}
                      {include file="Record/title-rating.tpl" ratingClass="searchStars" recordId=$resource.id shortId=$resource.shortId}
                      {/if}
	                  {/if}
	                  <div class="resultitem">
	                    {if $resource.id != null}<a href="{$url}/Record/{$resource.id|escape:"url"}" class="title">{/if}{$resource.title|regex_replace:"/(\/|:)$/":""|escape}{if $resource.id != null}</a>{/if}<br />
	                    {if $resource.author}
	                      {translate text='by'}: <a href="{$url}/Author/Home?author={$resource.author|escape:"url"}">{$resource.author|escape}</a><br />
	                    {/if}
	                    {if $resource.tags}
		                    {translate text='Your Tags'}:
		                    {foreach from=$resource.tags item=tag name=tagLoop}
		                      <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape}</a>{if !$smarty.foreach.tagLoop.last},{/if}
		                    {/foreach}
		                    <br />
	                    {/if}
	                    {if $resource.notes}
	                      {translate text='Notes'}: {$resource.notes|escape}<br />
	                    {/if}
	
	                    {if is_array($resource.format)}
	                      {foreach from=$resource.format item=format}
	                        <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
	                      {/foreach}
	                    {else}
	                      <span class="iconlabel {$resource.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$resource.format}</span>
	                    {/if}
	
	                    <br />
	                    
	                    <b>{translate text='Due'}: {$resource.duedate|escape}</b>
	                    
	                    {if $resource.renewMessage}
	                       <div class='{if $resource.renewResult == true}renewPassed{else}renewFailed{/if}'>
	                         {$resource.renewMessage|escape}
	                       </div>
	                    {/if}
	
	                  </div>
	                </div>
	
	              </div>
	            </li>
	          {/foreach}
	          </ul>
	          <script type="text/javascript">
	            $(document).ready(function() {literal} { {/literal}
	              doGetRatings();
	            {literal} }); {/literal}
	          </script>
	          {if $oneOrMoreRenewableItems}
		          <div class='selectAllControls'>
		            <a href="#" onclick="$('.titleSelect').attr('checked', true);return false;">Select All</a> /
		            <a href="#" onclick="$('.titleSelect').attr('checked', false);return false;"/>Deselect All</a>
		          </div>
		          <input type="submit" name="renewItems" value="Renew Selected Items" class="renewSelectedItems"/>
		          </form>
	          {/if}
          {else}
	          {translate text='You do not have any items checked out'}.
          {/if}
        {else}
          <div class="page">
          {include file="MyResearch/catalog-login.tpl"}
        {/if}</div>

    </div>
  </div>

</div>
