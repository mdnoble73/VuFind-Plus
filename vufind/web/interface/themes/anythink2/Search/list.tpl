<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
  {* Display spelling suggestions if any *}
  {if $spellingSuggestions}
  <div class="sidegroup">
    <h4>{translate text='spell_suggest'}</h4>
    <dl class="narrowList navmenu narrow_begin">
      {foreach from=$spellingSuggestions item=details key=term name=termLoop}
        <dd>{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}</dd>
      {/foreach}
    </dl>
  </div>
  {/if}
  {* Display facets *}
  {if $sideRecommendations}
    {foreach from=$sideRecommendations item="recommendations"}
      <div class="debug {$recommendations}">
        {include file=$recommendations}
      </div>
    {/foreach}
  {/if}
</div></div>

<div id="main-content">
  <div id="results-header">
    {if $recordCount}
      <h1>{$recordStart} - {$recordEnd} of {$recordCount} {translate text='results'} {if $searchType == 'basic'} {translate text='for'} <strong>{$lookfor|escape:"html"}</strong>{/if}</h1>
    {/if}
    <div id="results-actions-wrapper">
      <div id="results-actions">
        <form action="/Search/Results?lookfor={$lookfor|escape:"url"}">
          <label for="sort">{translate text='Sort by'}</label>
          <select id="sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;">
          {foreach from=$sortList item=sortData key=sortLabel}
            <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
          {/foreach}
          </select>
        </form>
      </div>
      <ul id="utility-links-results" class="inline left">
        {if !empty($user)}
          {if $savedSearch}
          <li><a class="button" href="{$url}/MyResearch/SaveSearch?delete={$searchId}">{translate text='save_search_remove'} -</a></li>
            {else}
          <li><a class="button" href="{$url}/MyResearch/SaveSearch?save={$searchId}">{translate text='save_search'} +</a></li>
          {/if}
        {/if}
        <li><a class="email" href="{$url}/Search/Email" class="mail" onclick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this'}</a></li>
        <li><a class="rss" href="{$rssLink|escape}" class="feed">{translate text='RSS feed'}</a></li>
      </ul>
    </div>
  </div>

  <div id="results-facets">
    {if $topRecommendations}
      {foreach from=$topRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
  </div>

  <div id="results-list">
  {if $subpage}
    {include file=$subpage}
  {else}
    {$pageContent}
  {/if}
  </div>

  <div id="results-bottom">
  {if $pageLinks.all}<div class="pagination" id="pagination-bottom">Page: {$pageLinks.all}</div>{/if}

  {if $enableMaterialsRequest}
  <div id="materialsRequestInfo">
  {translate text="Can't find what you are looking for?"} <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$smarty.request.lookfor|escape:url}&basicType={$smarty.request.basicType|escape:url}">{translate text="Request it!"}</a>.</div>
  </div>
  {/if}
  </div>

</div>