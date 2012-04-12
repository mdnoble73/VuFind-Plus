{if (isset($title)) }
<script type="text/javascript">
  alert("{$title}");
</script>
{/if}
<script  type="text/javascript" src="{$path}/js/holds.js"></script>
<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
      {if $user->cat_username}
        <div class="resulthead">
        <h3>{translate text='Your Holds'}</h3></div>
        <div class="page">
          {if is_array($recordList)}
            <div class='sortOptions'>
              {translate text='Sort'}
              <select name="sort" id="sort" onchange="changeSort($(this).val());">
              {foreach from=$sortOptions item=sortDesc key=sortVal}
                <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected{/if}>{translate text=$sortDesc}</option>
              {/foreach}
              </select>
            </div>
            <div class='selectAllControls'>
	            <a href="#" onclick="$('.titleSelect').attr('checked', true);return false;">Select All</a> /
	            <a href="#" onclick="$('.titleSelect').attr('checked', false);return false;"/>Deselect All</a>
            </div>
            <div class='clearer'></div>
            <ul class="filters">
		        {foreach from=$recordList item=resource name="recordLoop"}
		          {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
	              <li class="result alt">
	            {else}
	              <li class="result">
	            {/if}
	            <div class="yui-ge">
	              <div class="yui-u first">
	                {* Select Box for updating multiple items at one time. *}
	                <div class="selectTitle">
							      <input type="checkbox" name="selected[{$resource.xnum}~{$resource.cancelId|escape:"url"}~{$resource.cancelId|escape:"id"}]" id="selected{$resource.cancelId|escape:"url"}" class="titleSelect"/>&nbsp;
							    </div>
	                <img src="{$path}/bookcover.php?isn={$resource.isbn.0|@formatISBN}&amp;size=small&amp;upc={$resource.upc|escape:"url"}&amp;category={$resource.category|escape:"url"}" class="alignleft" alt='Cover Image'>
		              <div class="resultitem">
	                  {if $resource.id != null}<a href="{$url}/Record/{$resource.id|escape:"url"}" class="title">{/if}{$resource.title|regex_replace:"/(\/|:)$/":""|escape}{if $resource.id != null}</a>{/if}
			  
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
	
	                  <b>{translate text='Expires'}:</b> {$resource.expiredate|escape}
					          <br />
					          <b>{translate text='Pickup Location'}:</b> {$resource.currentPickupName}
					          <br />
	                  <b>{translate text='Status'}:</b>  {$resource.status} {if $resource.frozen}<span class='frozenHold' title='This hold will not be filled until you thaw the hold.'>(Frozen)</span>{/if}
	                  {if strlen($resource.freezeMessage) > 0}
                      <div class='{if $resource.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
                        {$resource.freezeMessage|escape}
                      </div>
                    {/if}
	                </div>
	              </div>
	            </div>
	          </li>
	        {/foreach}
	        </ul>
	        {* Code to handle updating multiple holds at one time *}
	        <div id='holdsWithSelected'>
	          <div class='selectAllControls'>
	          <a href="#" onclick="$('.titleSelect').attr('checked', true);return false;">Select All</a> /
	          <a href="#" onclick="$('.titleSelect').attr('checked', false);return false;"/>Deselect All</a>
            </div>
            <form name='withSelectedHoldsForm' action='{$path}/MyResearch/Home' method="get">
              <input type="hidden" name="withSelectedAction" value=""/>
		          <div id='holdsUpdateBranchSelction'>
				        Change Pickup Location for Selected Items to: 
				        {html_options name="withSelectedLocation" options=$pickupLocations selected=$resource.currentPickupId}
				        <input type="submit" name="updateSelected" value="Go" onclick="return updateSelectedHolds();"/>
              </div>
				      <div id='holdsUpdateSelected'>
				        {if $allowFreezeHolds}
	                <input type="submit" class="button" name="freezeSelected" value="Freeze Selected" title="Freezing a hold prevents the hold from being filled, but keeps your place in queue. This is great if you are going on vacation." onclick="return freezeSelectedHolds();"/>
	                <input type="submit" class="button" name="thawSelected" value="Thaw Selected" title="Thaw the hold to allow the hold to be filled again." onclick="return thawSelectedHolds();"/>
                {/if}
                <input type="submit" class="button" name="cancelSelected" value="Cancel Selected" onclick="return cancelSelectedHolds();"/>
				      </div>
			      </form>
	        </div>
        {else}
          {translate text='You do not have any holds placed'}.
        {/if}
      {else}
        <div class="page">
        {include file="MyResearch/catalog-login.tpl"}
      {/if}</div>
    </div>
  </div>

</div>
