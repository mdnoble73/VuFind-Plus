<div id="sidebar-wrapper"><div id="sidebar">
  <form method="post" action="{$path}/MyAccount/Home" id="loginForm">
    <h3>Login to your account</h3>
    <div class="form-item">
      <div><label for="username">{translate text='Username'}:</label></div>
      <div><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
    </div>
    <div class="form-item">
      <div><label for="password">{translate text='Password'}:</label></div>
      <div><input type="password" name="password" id="password" size="15"/></div>
      <div class="fine-print"><strong>Forgot PIN?</strong> <a href="{$path}/MyResearch/EmailPin">E-mail my PIN</a></div>
    </div>
    {if !$inLibrary}
      <div class="form-item"><input type="checkbox" id="rememberMe" name="rememberMe"/>&nbsp;<label for="rememberMe">{translate text="Remember Me"}</label></div>
    {/if}
    <div class="form-item"><input id="loginButton" type="submit" name="submit" value="Login" /></div>
    {if $followup}<input type="hidden" name="followup" value="{$followup}"/>{/if}
    {if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}"/>{/if}
    {if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}"/>{/if}
    {if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}"/>{/if}
    {if $comment}<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>{/if}
    {if $returnUrl}<input type="hidden" name="returnUrl" value="{$returnUrl}"/>{/if}
    {if $comment}
      <input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
    {/if}
  </form>
</div></div>
<div id="main-content">
  {if $message}<div class="error">{$message|translate}</div>{/if}
  <div class="get-card">
    <div><a id="get-card" href="{$path}/MyAccount/GetCard">Click here to get a card.</a></div>
    <h1>{translate text='Need an Anythink card?'}</h1>
    <h2>Click <a class="button" href="{$path}/MyAccount/GetCard">here</a> to get a card.</h2>
  </div>
  <script type="text/javascript">$('#username').focus();</script>
</div>