<div data-role="page" id="Search-reserves-list"  class="results-page">
  {include file="header.tpl"}
  <div data-role="content">
    {if !$recordCount}
      <p>{translate text="course_reserves_empty_list"}</p>
    {else}
      <p>
        {translate text="Showing"}
        <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
        {translate text='of'} <strong>{$recordCount}</strong>
        {translate text='Reserves'}
      </p>    
      {if $subpage}
        {include file=$subpage}
      {else}
        {$pageContent}
      {/if}
    {/if}
  </div>
  {include file="footer.tpl"}
</div>

{include file="Search/Recommend/SideFacets.tpl"}
