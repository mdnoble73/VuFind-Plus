<a rel="external" href="{$path}/Record/{$listId|escape:'url'}">
  <div class="result">
  <h3>
    {$listTitle|trim:':/'|escape}
  </h3>
  {if !empty($listAuthor)}
    <p>{translate text='by'} {$listAuthor}</p>
  {/if}
  {if $listTags}
    <p>
      <strong>{translate text='Your Tags'}:</strong>
      {foreach from=$listTags item=tag name=tagLoop}
        {$tag->tag|escape} {if !$smarty.foreach.tagLoop.last},{/if}
      {/foreach}
    </p>
  {/if}
  {if $listNotes}
    <p><strong>{translate text='Notes'}:</strong></p>
    {foreach from=$listNotes item=note}
      <p>{$note|escape}</p>
    {/foreach}
  {/if}
  
  {if !empty($listFormats)}
    <p>
    {foreach from=$listFormats item=format}
      <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
    {/foreach}
    </p>
  {/if}
  </div>
</a>
  {if $listEditAllowed}
      {* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
      <a class="delete_from_mylist"
      {if is_null($listSelected)}
        href="{$path}/MyResearch/Favorites?delete={$listId|escape:"url"}"
      {else}
        href="{$path}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$listId|escape:"url"}"
      {/if}
      rel="external">{translate text='Delete'}</a>
  {/if}  
