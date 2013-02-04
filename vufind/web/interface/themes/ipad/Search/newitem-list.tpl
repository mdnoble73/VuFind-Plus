<div data-role="page" id="Search-newitem-list"  class="results-page">
  {include file="header.tpl"}
  <div data-role="content">
    {if !$recordCount}
      <p>{translate text="No new item information is currently available."}</p>
    {else}
      <p>
        {translate text="Showing"}
        <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
        {translate text='of'} <strong>{$recordCount}</strong>
        {translate text='New Items'}
      </p>    
      {include file="Search/list-list.tpl"}
    {/if}
  </div>
  {include file="footer.tpl"}
</div>

{include file="Search/Recommend/SideFacets.tpl"}
