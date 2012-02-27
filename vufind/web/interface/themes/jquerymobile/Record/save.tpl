<h3>{translate text='add_favorite_prefix'} {$record.title|escape:"html"} {translate text='add_favorite_suffix'}</h3>

<form method="get" action="{$path}/Record/{$id|escape}/Save" name="saveRecord" data-ajax="false">
  <input type="hidden" name="submit" value="1" />
  <input type="hidden" name="id" value="{$id|escape}" />
  {if !empty($containingLists)}
  <ul data-role="listview" data-dividertheme="e" data-inset="true">
    <li data-role="list-divider">{translate text='This item is already part of the following list/lists'}:</li>
    {foreach from=$containingLists item="list"}
      <li><a rel="external" href="{$path}/MyResearch/MyList/{$list.id}">{$list.title|escape:"html"}</a></li>
    {/foreach}
  </ul>
  {/if}

{* Only display the list drop-down if the user has lists that do not contain
 this item OR if they have no lists at all and need to create a default list *}
{if (!empty($nonContainingLists) || (empty($containingLists) && empty($nonContainingLists))) }
  {assign var="showLists" value="true"}
{/if}

  {if $showLists}
  <div data-role="fieldcontain">
    <label for="save_list">{translate text='Choose a List'}</label>
    <select id="save_list" name="list">
      {foreach from=$nonContainingLists item="list"}
        <option value="{$list.id}"{if $list.id==$lastListUsed} selected="selected"{/if}>{$list.title|escape:"html"}</option>
        {foreachelse}
        <option value="">{translate text='My Favorites'}</option>
      {/foreach}
    </select>
  {/if}
  {* TODO:
    <a href="{$path}/MyResearch/ListEdit?id={$id|escape:"url"}" data-role="button" data-rel="dialog">{translate text="or create a new list"}</a>
  *}
  {if $showLists}  
    <label for="add_mytags">{translate text='Add Tags'}</label>
    <input id="add_mytags" type="text" name="mytags" value=""/>
    <p>{translate text='add_tag_note'}</p>
    <label for="add_notes">{translate text='Add a Note'}</label>
    <textarea id="add_notes" name="notes"></textarea>
  </div>
  <div data-role="fieldcontain">
    <input class="button" type="submit" value="{translate text='Save'}"/>
  </div>
  {/if}
  

    
</form>
