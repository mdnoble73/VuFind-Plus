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
    <input type="submit" name="submit" value="{translate text='Login'}"/>
  </div>
    <input type="hidden" name="overDriveId" value="{$overDriveId}"/>
    <input type="hidden" name="formatId" value="{$formatId}"/>
  </form>
  {if $authMethod == 'DB'}<a rel="external" data-role="button" href="{$path}/MyResearch/Account">{translate text='Create New Account'}</a>{/if}
{/if}