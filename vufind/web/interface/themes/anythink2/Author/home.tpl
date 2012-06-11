<div id="sidebar-wrapper"><div id="sidebar">
    {if $enrichment.novelist.similarAuthorCount != 0}
    <div class="sidegroup">
      <h4>{translate text="Similar Authors"}</h4>
      <ul class="similar">
        {foreach from=$enrichment.novelist.authors item=similar}
        <li>
          <a href={$url}/Author/Home?author={$similar|escape:"url"}>{$similar}</a>
          </span>
        </li>
        {/foreach}
      </ul>
    </div>
    {/if}
      {* Recommendations *}
      {if $sideRecommendations}
        {foreach from=$sideRecommendations item="recommendations"}
          {include file=$recommendations}
        {/foreach}
      {/if}
      {* End Recommendations *}
</div></div>
<div id="main-content">
   {if $info}
     <div class="authorbio yui-ge">
     <h1>{$info.name|escape}</h1>
     {if $info.image}
     <img src="{$info.image}" alt="{$info.altimage|escape}" width="150px" class="alignleft recordcover">
     {/if}
     {$info.description|truncate_html:4500:"...":false}
     <p><a href="http://{$wiki_lang}.wikipedia.org/wiki/{$info.name|escape:"url"}" target="new"><span class="note">{translate text='wiki_link'}</span></a></p>
     </div>
   {/if}
   <div id="results-header">
     {if $recordCount}
       <h1>{$recordStart} - {$recordEnd} of {$recordCount} {translate text='results'}</h1>
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
   <div id="results-list">
     {include file=Search/list-list.tpl}
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
