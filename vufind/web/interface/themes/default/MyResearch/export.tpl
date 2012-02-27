<div align="left">
  {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
  {if $infoMsg}<div class="userMsg">{$infoMsg|translate}</div>{/if}

  <div id="popupMessages"></div>
  <div id="popupDetails"> 
    <form action="{$url}/MyResearch/Export" method="POST" onSubmit='exportIDS(this.elements[&quot;ids[]&quot;], this.elements[&quot;format&quot;].value,
      {* Pass translated strings to Javascript -- ugly but necessary: *}
      {literal}{{/literal}exporting: &quot;{translate text='export_exporting'}&quot;, 
       success: &quot;{translate text='export_success'}&quot;,
       download: &quot;{translate text='export_download'}&quot;,
       failure: &quot;{translate text='export_failure'}&quot;{literal}}{/literal}
      ); return false;'>
      <table>
        {foreach from=$exportList item=favorite}
        <tr>
          <th class="label">{translate text='Title'}:</th>
          <td>{$favorite.title|escape}</td>
        </tr>
        {/foreach}
        <tr>
          <th class="label"><label for="format">{translate text="Format"}:</label></th>
          <td>
              <select name="format" id="format">
              {foreach from=$exportOptions item=exportOption}
                <option value="{$exportOption|escape}">{translate text=$exportOption}</option>
              {/foreach}
              </select>
          </td>
        </tr>
        <tr>
          <th>&nbsp;</th>
          <td><input class="submit" type="submit" name="submit" value="{translate text='Export'}">
              {foreach from=$exportIDS item=exportID}
                <input type="hidden" name="ids[]" value="{$exportID|escape}" />
              {/foreach}
          </td>
        </tr>
      </table>
      {if $listID}
        <input type="hidden" name="listID" value="{$listID|escape}" />
      {/if}
      {if $followupModule}
      <input type="hidden" name="followupModule" value="{$followupModule|escape}" />
      {/if}
      {if $followupAction}
      <input type="hidden" name="followupAction" value="{$followupAction|escape}" />
      {/if}
    </form>
  </div>
</div>
