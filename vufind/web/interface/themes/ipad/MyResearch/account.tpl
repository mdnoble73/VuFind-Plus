<div data-role="page" id="MyResearch-account">
  {include file="header.tpl"}
  <div data-role="content">
    {if $message}<div class="error">{$message|translate}</div>{/if}
    
    <form method="post" action="{$path}/MyResearch/Account" name="accountForm" data-ajax="false">
      <div data-role="fieldcontain">
        <label for="account_firstname">{translate text="First Name"}:</label>
        <input id="account_firstname" type="text" name="firstname" value="{$formVars.firstname|escape}"/>
        <label for="account_lastname">{translate text="Last Name"}:</label>
        <input id="account_lastname" type="text" name="lastname" value="{$formVars.lastname|escape}"/>
        <label for="account_email">{translate text="Email Address"}:</label>
        <input id="account_email" type="email" name="email" value="{$formVars.email|escape}"/>
        <label for="account_username">{translate text="Desired Username"}:</label>
        <input id="account_username" type="text" name="username" value="{$formVars.username|escape}"/>
        <label for="account_password">{translate text="Password"}:</label>
        <input id="account_password" type="password" name="password"/>
        <label for="account_password2">{translate text="Password Again"}:</label>
        <input id="account_password2" type="password" name="password2"/>
      </div>
      <div data-role="fieldcontain">
        <input type="submit" name="submit" value="{translate text="Submit"}"/>
      </div>
    </form>
  </div>    
  {include file="footer.tpl"}
</div>
