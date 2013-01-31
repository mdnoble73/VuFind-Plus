<div data-role="page" id="Search-list" class="results-page">
  {include file="header.tpl"}
  <div data-role="content">
    {if $recordCount}
    <p>
      <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
      {translate text='of'} <strong>{$recordCount}</strong>
      {if $searchType == 'basic'}{translate text='for'}: <strong>{$lookfor|escape:"html"}</strong>{/if}
    </p>
    {/if}
    {if $subpage}
      {include file=$subpage}
    {else}
      {$pageContent}
    {/if}
  </div>
  {include file="footer.tpl"}
</div>

{include file="Search/Recommend/SideFacets.tpl"}
