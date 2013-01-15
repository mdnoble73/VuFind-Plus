AJAX Login
{if $message}<div class="error">{$message|translate}</div>{/if}
<form method="post" action="{$path}/MyResearch/Home" name="loginForm"
      onSubmit="Login(this, '{$followupModule}', '{$followupAction}', '{$recordId}', null, '{$title|escape}'); {$followUp} return false;">
<input type="hidden" name="salt" value="">
<table class="citation">
  <tr>
    <td>{translate text='Username'}: </td>
    <td><input id="mainFocus" type="text" name="username" value="{$username|escape:"html"}" size="15"></td>
  </tr>

  <tr>
    <td>{translate text='Password'}: </td>
    <td>
    	<input type="password" name="password" size="15">
    	<br/>
    	<input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label>
    	{if !$inLibrary}
    	<br/>
    	<input type="checkbox" id="rememberMe" name="rememberMe"/><label for="rememberMe">{translate text="Remember Me"}</label>
    	{/if}
    </td>
  </tr>

  <tr>
    <td></td>
    <td><input type="submit" name="submit" value="{translate text='Login'}"></td>
  </tr>
</table>
</form>

{if $authMethod == 'DB'}
<a href="{$path}/MyResearch/Account">{translate text='Create New Account'}</a>
{/if}