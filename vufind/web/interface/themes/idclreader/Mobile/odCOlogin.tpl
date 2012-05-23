{if $message}<div class="error">{$message|translate}</div>{/if}
{if $authMethod != 'Shibboleth'}
<form method="post" action="" name="loginForm" data-ajax="false">
  <div data-role="fieldcontain">
    <label for="username">{translate text='Username'}:</label>
    <input id="username" type="text" name="username" value="{$username|escape}"/>
  </div>
  <div data-role="fieldcontain">
    <label for="password">{translate text='Password'}:</label>
    <input id="password" type="password" name="password"/>
  </div>
  <div data-role="fieldcontain">
    <input type="submit" name="submit" value="{translate text='Check Out OverDrive Item'}"/>
  </div>
    {if $followup}<input type="hidden" name="followup" value="{$followup}"/>{/if}
    {if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}"/>{/if}
    {if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}"/>{/if}
    {if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}"/>{/if}
    {if $comment}<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>{/if}
  </form>
  {if $authMethod == 'DB'}<a rel="external" data-role="button" href="{$path}/MyResearch/Account">{translate text='Create New Account'}</a>{/if}
{/if}