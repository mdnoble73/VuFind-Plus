<div data-role="page" id="Search-list" class="results-page">
  {include file="header.tpl"}
  <div data-role="content">
    {if $info}
      <div class="authorbio">
        <h2>{$info.name|escape}</h2>

        {if $info.image}
          <img src="{$info.image}" alt="{$info.altimage|escape}" width="150px" /><br/>
        {/if}

        {$info.description|truncate_html:4500:"...":false}

        <div class="providerLink"><a class="wikipedia" href="http://{$wiki_lang}.wikipedia.org/wiki/{$info.name|escape:"url"}" target="new">{translate text='wiki_link'}</a></div>
      </div>
    {/if}
    {if $recordCount}
    <p>
      <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
      {translate text='of'} <strong>{$recordCount}</strong>
      {translate text='for search'}: <strong>'{$authorName|escape:"html"}'</strong>
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
