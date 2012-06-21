<div id="record{$listId|escape}" class="resultsList">
<div class="selectTitle">
  <input type="checkbox" name="selected[{$listId|escape:"url"}]" id="selected{$listId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('{$listId|escape:"url"}', '{$listTitle|regex_replace:"/(\/|:)$/":""|escape:"javascript"}', this);"{/if} />&nbsp;
</div>
        
<div class="imageColumn"> 
    <div id='descriptionPlaceholder{$listId|escape}' style='display:none'></div>
    <a href="{$url}/Record/{$listId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$listId|escape:"url"}">
    <img src="{$path}/bookcover.php?id={$listId}&amp;isn={$listISBN|@formatISBN}&amp;size=small&amp;upc={$listUPC}&amp;category={$listFormatCategory|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
    </a>
</div>

<div class="resultDetails">
  <div class="resultItemLine1">
	<a href="{$url}/Record/{$listId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$listTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$listTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
	{if $listTitleStatement}
    <div class="searchResultSectionInfo">
      {$listTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
    </div>
    {/if}
  </div>

  <div class="resultItemLine2">
    {if $listAuthor}
      {translate text='by'}
      {if is_array($listAuthor)}
        {foreach from=$listAuthor item=author}
          <a href="{$url}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
        {/foreach}
      {else}
        <a href="{$url}/Author/Home?author={$listAuthor|escape:"url"}">{$listAuthor|highlight:$lookfor}</a>
      {/if}
    {/if}
    
    {if $listTags}
      {translate text='Your Tags'}:
      {foreach from=$listTags item=tag name=tagLoop}
        <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a>{if !$smarty.foreach.tagLoop.last},{/if}
      {/foreach}
      <br>
    {/if}
    {if $listNotes}
      {translate text='Notes'}: 
      {foreach from=$listNotes item=note}
        {$note|escape:"html"}<br>
      {/foreach}
    {/if}
 
    {if $listDate}{translate text='Published'} {$listDate.0|escape}{/if}
  </div>

  {if is_array($listFormats)}
    {foreach from=$listFormats item=format}
      <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
    {/foreach}
  {else}
    <span class="iconlabel {$listFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$listFormats}</span>
  {/if}
  <div id = "holdingsSummary{$listId|escape:"url"}" class="holdingsSummary">
    <div class="statusSummary" id="statusSummary{$listId|escape:"url"}">
      <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
    </div>
  </div>
</div>

<div id ="searchStars{$listId|escape}" class="resultActions">
  <div class="rate{$listId|escape} stat">
    {* Place hold link *}
    <div class='requestThisLink' id="placeHold{$listId|escape:"url"}" style="display:none">
      <a href="{$url}/Record/{$listId|escape:"url"}/Hold"><img src="{img filename=place_hold.png}" alt="Place Hold"/></a>
    </div>
      <div id="saveLink{$listId|escape}">
        {if $listEditAllowed}
		    <a href="{$url}/MyResearch/Edit?id={$listId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}" class="edit tool">{translate text='Edit'}</a>
		    {* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
		    <a
		    {if is_null($listSelected)}
		      href="{$url}/MyResearch/Home?delete={$listId|escape:"url"}"
		    {else}
		      href="{$url}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$listId|escape:"url"}"
		    {/if}
		    class="delete tool" onclick="return confirm('Are you sure you want to delete this?');">{translate text='Delete'}</a>
		{/if}
      </div>
    </div>
      
  </div>
<script type="text/javascript">
 $(document).ready(function(){literal} { {/literal}
    addIdToStatusList('{$listId|escape:"javascript"}');
    resultDescription('{$listId}','{$listId}');
  {literal} }); {/literal}
  
</script>

</div>

