<div class="yui-ge">
  
  <div class="listEntryLeft">
    <a name="record{$listId|escape:"url"}" ></a>
	  <div class="selectTitle">
	    <input type="checkbox" name="selected[{$listShortId|escape:"url"}]" id="selected{$listShortId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('{$listId|escape:"url"}', '{$listTitle|regex_replace:"/(\/|:)$/":""|regex_replace:"/\"/":"&quot;"|escape:"javascript"}', this);"{/if} />&nbsp;
	  </div>
  
    <img src="{$path}/bookcover.php?isn={$listISBN|escape:"url"}&amp;size=small&amp;category={$record.format_category.0|escape:"url"}" alt='Cover Image' class="alignleft">

    <div class="resultitem">
      <a href="{$url}/Record/{$listId|escape:"url"}" class="title">{$listTitle|regex_replace:"/(\/|:)$/":""|escape}</a><br />
      {if $listAuthor}
        {translate text='by'}: <a href="{$url}/Author/Home?author={$listAuthor|escape:"url"}">{$listAuthor|escape}</a><br />
      {/if}
      {if $listTags}
        {translate text='Your Tags'}:
        {foreach from=$listTags item=tag name=tagLoop}
          <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a>{if !$smarty.foreach.tagLoop.last},{/if}
        {/foreach}
        <br />
      {/if}
      {if $listNotes}
        {translate text='Notes'}: 
        {foreach from=$listNotes item=note}
          {$note|escape:"html"}<br />
        {/foreach}
      {/if}

      {foreach from=$listFormats item=format}
        <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
      {/foreach}
    </div>
  </div>

  <div class="listEntryRight">
    <div id = "holdingsSummary{$listShortId|escape:"url"}" class="holdingsSummary">
        <div class="statusSummary" id="statusSummary{$listShortId|escape:"url"}">
          <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
        </div>
        
        <div style='display:none' id="placeHold{$listShortId|escape:"url"}"><a href="{$url}/Record/{$listId|escape:"url"}/Hold" class="hold">{translate text = 'Request This Title'}&nbsp; &nbsp;</a></div>
        <div style="display:none" id="callNumber{$listShortId|escape:"url"}"></div>
        <div style="display:none" id="downloadLink{$listShortId|escape:"url"}"></div>
        <div style ="display:none;color:#646464;font-size:8pt;" id="copyInfo{$listShortId|escape:"url"}"></div>
    </div>
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
  <script type="text/javascript">
		addIdToStatusList('{$record.id|escape:"javascript"}');
	</script>
</div>
<div class="clearer">&nbsp;</div>