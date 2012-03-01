<script  type="text/javascript" src="{$path}/services/Search/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
    alert("{$title}");
</script>
{/if}
<script type="text/javascript" src="{$path}/js/readingHistory.js" /></script>
<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
        {if $user->cat_username}
          <div class="resulthead">
          <h3>{translate text='My Reading History'} {if $historyActive == true}<a id='readingListWhatsThis' onclick="$('#readingListDisclaimer').toggle();return false;">(What's This?)</a>{/if}</h3>
          <div id='readingListDisclaimer' {if $historyActive == true}style='display: none'{/if}>
          The library takes seriously the privacy of your library records. Therefore, we do not keep track of what you borrow after you return it. 
          However, our automated system has a feature called "My Reading History" that allows you to track items you check out. 
          Participation in the feature is entirely voluntary. You may start or stop using it, as well as delete any or all entries in "My Reading History" at any time. 
          If you choose to start recording "My Reading History", you agree to allow our automated system to store this data. 
          The library staff does not have access to your "My Reading History", however, it is subject to all applicable local, state, and federal laws, and under those laws, could be examined by law enforcement authorities without your permission. 
          If this is of concern to you, you should not use the "My Reading History" feature.
          </div>
          </div>
          <div class="page">
          <form name='readingList' id='readingListForm' action='{$path}/MyResearch/ReadingHistory' method='get'>
          <input name='readingHistoryAction' id='readingHistoryAction' value='' type='hidden' />
          {if $transList}
            <div class='sortOptions'>
	             {translate text='Sort'}
	             <select name="sort" id="sort" onchange="changeSort($(this).val());">
	             {foreach from=$sortOptions item=sortDesc key=sortVal}
	               <option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected{/if}>{translate text=$sortDesc}</option>
	             {/foreach}
	             </select>
            </div>
          {/if}
          <div id="readingListActionsTop">
            {if $historyActive == true}
              {if $transList}
		            <button value="deleteMarked" class="RLdeleteMarked" onclick='return deletedMarkedAction()'>Delete Marked</button>
		            <button value="deleteAll" class="RLdeleteAll" onclick='return deleteAllAction()'>Delete All</button>
	            {/if}
	            {* <button value="exportList" class="RLexportList" onclick='return exportListAction()'>Export Reading History</button> *}
	            <button value="optOut" class="RLoptOut" onclick='return optOutAction({if $transList}true{else}false{/if})'>Stop Recording My Reading History</button>
	          {else}
	            <button value="optIn" class="RLoptIn" onclick='return optInAction()'>Start Recording My Reading History</button> 
	          {/if}
          </div>
          <div class="clearer"></div>
          {if $transList}
            <div class="resulthead">Showing <b>{$startRecord}</b> - <b>{$endRecord}</b> of <b>{$numRecords}</b> items.</div>
	          <ul class="filters">
	          {foreach from=$transList item=resource name="recordLoop"}
	            {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
	            <li class="result alt">
	            {else}
	            <li class="result">
	            {/if}
	              <div class="yui-ge">
	                <div >
	                  <div class="selectTitle">
										  <input type="checkbox" name="selected[]" value="rsh{$resource.itemindex}" id="rsh{$resource.itemindex}"/>&nbsp;
										</div>
	                  <img src="{$path}/bookcover.php?isn={$resource.isbn|@formatISBN}&amp;size=small&amp;category={$resource.format_category.0|escape:"url"}" class="alignleft" alt='Cover Image'>

                    {if $resource.id != null && $showRatings == 1}
                      {* Let the user rate this title *}
                      {include file="Record/title-rating.tpl" ratingClass="searchStars" recordId=$resource.id shortId=$resource.shortId}
                    {/if}
                      	
	                  <div class="resultitem">
	                    {if $resource.id != null}<a href="{$url}/Record/{$resource.id|escape:"url"}" class="title">{/if}{$resource.title|escape}{if $resource.id != null}</a>{/if}<br />
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
	                    
	                    <b>{translate text='Checked Out'}: {$resource.checkout|escape}, {$resource.details|escape}</b>
						  <div class="clearer"></div>
						
	                  </div>
	                </div>
	
	              </div>
	            </li>
	          {/foreach}
	          
	          </ul>
	          {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
	          <script type="text/javascript">
						$(document).ready(function() {literal} { {/literal}
						  doGetRatings();
						{literal} }); {/literal}
						</script>
          {else if $historyActive == true}
            {* No Items in the history, but the history is active *}
            {translate text='You do not have any items in your reading list'}.
          {/if}
          {if $transList} {* Don't double the actions if we don't have any items *}
	          <div id="readingListActionsBottom">
	            {if $historyActive == true}
	              {if $transList}
	                <button value="deleteMarked" class="RLdeleteMarked" onclick='return deletedMarkedAction()'>Delete Marked</button>
	                <button value="deleteAll" class="RLdeleteAll" onclick='return deleteAllAction()'>Delete All</button>
	              {/if}
	              {* <button value="exportList" class="RLexportList" onclick='return exportListAction()'>Export Reading History</button> *}
	              <button value="optOut" class="RLoptOut" onclick='return optOutAction({if $transList}true{else}false{/if})'>Stop Recording My Reading History</button>
	            {else}
	              <button value="optIn" class="RLoptIn" onclick='return optInAction()'>Start Recording My Reading History</button> 
	            {/if}
	          </div>
          {/if}
          </form>
          </div>
        {else}
          <div class="page">
            {include file="MyResearch/catalog-login.tpl"}
          </div>
        {/if}
    </div>
  </div>

</div>
