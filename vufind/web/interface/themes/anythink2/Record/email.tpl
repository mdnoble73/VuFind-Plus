{if $lightbox}
<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
  <a onclick="hideLightbox(); return false;" href="">close</a>
  {translate text='Email Title'}
</div>
<div id="popupboxContent" class="content">
{/if}
<div align="left">
  {if $message}<div class="error">{$message|translate}</div>{/if}

  <form action="{$path}/Record/{$id|escape:"url"}/Email" method="post" id="popupForm" name="popupForm"
        onSubmit='SendEmail(&quot;{$id|escape}&quot;, this.elements[&quot;to&quot;].value,
        this.elements[&quot;from&quot;].value, this.elements[&quot;message&quot;].value,
        {* Pass translated strings to Javascript -- ugly but necessary: *}
        {literal}{{/literal}sending: &quot;{translate text='email_sending'}&quot;, 
         success: &quot;{translate text='email_success'}&quot;,
         failure: &quot;{translate text='email_failure'}&quot;{literal}}{/literal}
        ); return false;'>
    <div>
    <b>{translate text='To (email):'}</b><br />
    <input type="text" name="to" size="40"><br />
    <b>{translate text='From (email):'}</b><br />
    <input type="text" name="from" size="40"><br />
    <b>{translate text='Message:'}</b><br />
    <textarea name="message" rows="3" cols="40"></textarea><br />
    <input type="submit" name="submit" value="{translate text='Send'}">
    </div>
  </form>
</div>
{if $lightbox}
</div>
{/if}