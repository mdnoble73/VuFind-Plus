{if $message}<div class="error">{$message|translate}</div>{/if}
<div id='loginFormWrapper'>
  <form method="post" action="{$url}/MyResearch/Home" name="loginForm" onSubmit="Login(this, '{$followupModule}', '{$followupAction}', '{$recordId}', null, '{$title|escape}'); {$followUp} return false;">
    <div id='loginFormFields'>
    <input type="hidden" name="salt" value="">
    <div id='haveCardLabel' class='loginFormRow'>I have a Wake County Library Card</div>
      <div id ='loginUsernameRow' class='loginFormRow'>
        <div class='loginLabel'>{translate text='Username'}: </div>
        <div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
      </div>
      <div id ='loginPasswordRow' class='loginFormRow'>
        <div class='loginLabel'>{translate text='Password'}: </div>
        <div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
      </div>
      <div id='loginSubmitButtonRow' class='loginFormRow'>
        <input id="loginButton" type="image" name="submit" value="Login" src='{$path}/interface/themes/wcpl/images/login.png' alt='{translate text="Login to your account"}' />
      </div>
    </div>
  </form>
  <script type="text/javascript">$('#username').focus();</script>
</div>
<div id='needACardWrapper'>
  <div id='needCardLabel' class='loginFormRow'>
  <a href="{$path}/MyResearch/GetCard">I need a Wake County Library Card</a>
  </div>
  <div class='loginFormRow'>
  <a href="{$path}/MyResearch/GetCard"><img src="{$path}/interface/themes/{$theme}/images/wcpl_card.jpg" alt="Get a Library Card" /></a>
  </div>
</div>
<div id='retreiveLoginInfo'>
  <div id='emailPinLabel' class='loginFormRow'>
    <a href="{$path}/MyResearch/EmailPin">EMAIL MY PIN</a>
  </div>
</div>