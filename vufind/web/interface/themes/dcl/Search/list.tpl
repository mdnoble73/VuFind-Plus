<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
  <div id="page-content" class="content">
    {* Narrow Search Options *}
	<div id="sidebar">
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
	      {include file=$recommendations}
	    {/foreach}
	  {/if}
	</div>
	{* End Narrow Search Options *}
	
    <div id="main-content">
    
      <div id="searchInfo">
        {if $recordCount}
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {translate text='of'} <b>{$recordCount}</b>
          {if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
        {/if}
        {translate text='query time'}: {$qtime}s
        <select name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;">
        {foreach from=$sortList item=sortData key=sortLabel}
          <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text='Sort by '} {translate text=$sortData.desc}</option>
        {/foreach}
        </select>
        
      </div>
      <div id="searchResultTopTools">
      	{if $pageLinks.all}<div class="pagination">Page: {$pageLinks.all}</div>{/if}
      	
      	<div id="searchResultTopToolLinks">
	      	<div class='searchtool-top'><a href="{$rssLink|escape}" class="feed">{translate text='Get RSS Feed'}</a></div>
	        <div class='searchtool-top'><a href="{$url}/Search/Email" class="mail" onclick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this Search'}</a></div>
	        <div class='searchtool-top'>{if $savedSearch}<a href="{$url}/MyResearch/SaveSearch?delete={$searchId}" class="delete">{translate text='save_search_remove'}</a>{else}<a href="{$url}/MyResearch/SaveSearch?save={$searchId}" class="add">{translate text='save_search'}</a>{/if}</div>
        </div>
      </div>
      {* Recommendations *}
      {if $topRecommendations}
        {foreach from=$topRecommendations item="recommendations"}
          {include file=$recommendations}
        {/foreach}
      {/if}

      {if $subpage}
        {include file=$subpage}
      {else}
        {$pageContent}
      {/if}

      {if $pageLinks.all}<div class="pagination" id="pagination-bottom">Page: {$pageLinks.all}</div>{/if}
      <div class="searchtools">
        <a href="{$rssLink|escape}" class="feed">{translate text='Get RSS Feed'}</a>
        <a href="{$url}/Search/Email" class="mail" onclick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this Search'}</a>
        {if $savedSearch}<a href="{$url}/MyResearch/SaveSearch?delete={$searchId}" class="delete">{translate text='save_search_remove'}</a>{else}<a href="{$url}/MyResearch/SaveSearch?save={$searchId}" class="add">{translate text='save_search'}</a>{/if}
      </div>
      
    </div>
    {* End Main Listing *}
    
  </div>

