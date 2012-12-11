{if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
{if $infoMsg}<div class="info">{$infoMsg|translate}</div>{/if}

<form action="{$path}{$formTargetPath|escape}" method="post"  name="emailRecord" data-ajax="false">
    <input type="hidden" name="id" value="{$id|escape}" />
    <input type="hidden" name="type" value="{$module|escape}" />
  <div data-role="fieldcontain">
    <label for="email_to">{translate text='To'}:</label>
    <input id="email_to" type="email" name="to"/>
    <label for="email_from">{translate text='From'}:</label>
    <input id="email_from" type="email" name="from"/>
    <label for="email_message">{translate text='Message'}:</label>
    <textarea id="email_message" name="message"></textarea>
  </div>
  <div data-role="fieldcontain">
    <input class="button" type="submit" name="submit" value="{translate text='Send'}"/>
  </div>
</form>
