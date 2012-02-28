<div id="page-content" class="content">
	<div id="sidebar">
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
	   
	</div>
	
	<div id="main-content">
       {if $info}
       <div class="authorbio yui-ge">
       <h2>{$info.name|escape}</h2>
 
       {if $info.image}
       <img src="{$info.image}" alt="{$info.altimage|escape}" width="150px" class="alignleft recordcover">
       {/if}
       {$info.description|truncate_html:4500:"...":false}
       <p>
          <a href="http://{$wiki_lang}.wikipedia.org/wiki/{$info.name|escape:"url"}" target="new"><span class="note">{translate text='wiki_link'}</span></a></p>
       <div class="clearer" ></div>
       </div>
       {/if}
 
       {* Listing Options *}
      <div class="yui-ge resulthead">
        <div class="yui-u first">
        {if $recordCount}
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {translate text='of'} <b>{$recordCount}</b>
          {if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
        {/if}
          {translate text='query time'}: {$qtime}s
          
        </div>

        <div class="yui-u toggle">
          {translate text='Sort'}
          <select name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;">
          {foreach from=$sortList item=sortData key=sortLabel}
            <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
          {/foreach}
          </select>
        </div>

      </div>

       {include file=Search/list-list.tpl}

       {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
  
      <div class="searchtools">
        <strong>{translate text='Search Tools'}:</strong>
        <a href="{$rssLink|escape}" class="feed">{translate text='Get RSS Feed'}</a>
        <a href="{$url}/Search/Email" class="mail" onclick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this Search'}</a>
      </div>
    </div>
</div>
