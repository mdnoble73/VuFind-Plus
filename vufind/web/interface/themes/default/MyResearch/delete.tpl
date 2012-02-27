<div align="left">
  {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
  {if $infoMsg}<div class="userMsg">{$infoMsg|translate}</div>{/if}

  <div id="popupMessages"></div>
  <div id="popupDetails">
     {if !$listID}
      <div class="userMsg">{translate text='fav_delete_warn'}</div>
    {else}
      <h2>{translate text="List"}: {$list->title|escape}</h2>
    {/if}
    <form action="{$url}/MyResearch/Delete" method="POST" onSubmit='deleteFavorites(this.elements[&quot;ids[]&quot;],this.elements[&quot;listID&quot;].value,
      {* Pass translated strings to Javascript -- ugly but necessary: *}
      {literal}{{/literal}deleting: &quot;{translate text='fav_delete_deleting'}&quot;,
       success: &quot;{translate text='fav_delete_success'}&quot;,
       failure: &quot;{translate text='fav_delete_fail'}&quot;{literal}}{/literal}
      ); return false;'>
      <table>
        {foreach from=$deleteList item=favorite}
        <tr>
          <th class="label">{translate text='Title'}:</th>
          <td>{$favorite.title|escape}</td>
        </tr>
        {/foreach}
        <tr>
          <th>&nbsp;</th>
          <td><input class="submit" type="submit" name="submit" value="{translate text='Delete'}">
              {foreach from=$deleteIDS item=deleteID}
                <input type="hidden" name="ids[]" value="{$deleteID|escape}" />
              {/foreach}
          </td>
        </tr>
      </table>
      <input type="hidden" name="listID" value="{if $listID}{$listID|escape}{/if}" />
      {if $followupModule}
      <input type="hidden" name="followupModule" value="{$followupModule|escape}" />
      {/if}
      {if $followupAction}
      <input type="hidden" name="followupAction" value="{$followupAction|escape}" />
      {/if}
    </form>
  </div>
</div>
