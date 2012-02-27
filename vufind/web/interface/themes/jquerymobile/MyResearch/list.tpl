<div data-role="page" id="MyResearch-list">
  {include file="header.tpl"}
  <div data-role="content">
    {if $listList}
    <div data-role="collapsible" data-collapsed="true">
      <h3>{translate text='Your Lists'}</h3>
      <ul class="mylists" data-role="listview" data-inset="true" data-dividertheme="e">
        {foreach from=$listList item=listItem}
        <li>
          {if $list && $listItem->id == $list->id}
            {$listItem->title|escape}
          {else}
            <a rel="external" href="{$path}/MyResearch/MyList/{$listItem->id}">{$listItem->title|escape}</a>
          {/if}
          <span class="ui-li-count">{$listItem->cnt}</span>
        </li>
        {/foreach}
      </ul>
    </div>
    {/if}  
  
    {if $list}
      <h3>{$list->title|escape}</h3>
      {if $list->description}<p>{$list->description|escape}</p>{/if}
    {else}
      <h3>{translate text="Your Favorites"}</h3>
    {/if}
    
    {if !empty($resourceList)}
      <p>
        <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
        {translate text='of'} <strong>{$recordCount}</strong>
      </p>
    
      <ul class="results mylist" data-role="listview" data-split-icon="minus" data-split-theme="d" data-inset="false">
        {foreach from=$resourceList item=resource name="recordLoop"}
        <li>
          {* This is raw HTML -- do not escape it: *}
          {$resource}
        </li>
        {/foreach}

      <div data-role="controlgroup" data-type="horizontal" align="center">
        {if $pageLinks.back}
          {$pageLinks.back|replace:'<a ':'<a data-role="button" data-rel="back" '}
        {/if}
        {if $pageLinks.next}
          {$pageLinks.next|replace:'<a ':'<a data-role="button" '}
        {/if}
      </div>
    {else}
    	{if $list}
      	<p>{translate text='You do not have any saved resources'}</p>
      {else}
      	<p>{translate text='Please select a list to view titles.'}</p>
      {/if}      
    {/if}
  </div>
  {include file="footer.tpl"}
</div>
