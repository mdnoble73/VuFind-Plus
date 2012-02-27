<div align="left">
  {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
  {if $infoMsg}<div class="userMsg">{$infoMsg|translate}</div>{/if}

  <div id="popupMessages"></div>
  <div id="popupDetails"> 
    <form action="{$url}/MyResearch/Email" method="post" onSubmit='SendIDEmail(this.elements[&quot;to&quot;].value, 
      this.elements[&quot;from&quot;].value, this.elements[&quot;ids[]&quot;], this.elements[&quot;message&quot;].value,
      {* Pass translated strings to Javascript -- ugly but necessary: *}
      {literal}{{/literal}sending: &quot;{translate text='email_sending'}&quot;, 
       success: &quot;{translate text='email_success'}&quot;,
       failure: &quot;{translate text='email_failure'}&quot;{literal}}{/literal}
      ); return false;'>
      <table>
        {foreach from=$emailList item=favorite}
        <tr>
          <th class="label">{translate text='Title'}:</th>
          <td>{$favorite.title|escape}</td>
        </tr>
        {/foreach}
        <tr>
          <th class="label"><label for="to">{translate text='To'}:</label></th>
          <td><input type="text" name="to" id="to" size="40" value="{$formTo|escape}"/></td>
        </tr>
        <tr>
          <th class="label"><label for="from">{translate text='From'}:</label></th>
          <td><input type="text" name="from" id="from" size="40" value="{$formFrom|escape}"/></td>
        </tr>
        <tr>
          <th class="label"><label for="message">{translate text='Message'}:</label></th>
          <td><textarea name="message" id="message" rows="3" cols="40">{$formMessage|escape}</textarea></td>
        </tr>
        <tr>
          <th>&nbsp;</th>
          <td><input class="submit" type="submit" name="submit" value="{translate text='Send'}"/></td>
        </tr>
      </table>
      {foreach from=$emailIDS item=emailID}
      <input type="hidden" name="ids[]" value="{$emailID|escape}"/>
      {/foreach}
      {if $listID}
        <input type="hidden" name="listID" value="{$listID|escape}"/>
      {/if}
      {if $followupModule}
      <input type="hidden" name="followupModule" value="{$followupModule|escape}"/>
      {/if}
      {if $followupAction}
      <input type="hidden" name="followupAction" value="{$followupAction|escape}"/>
      {/if}
    </form>
  </div>
</div>
