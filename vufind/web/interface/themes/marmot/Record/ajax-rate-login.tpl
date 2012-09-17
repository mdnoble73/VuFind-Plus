<div onmouseup="this.style.cursor='default';" onmousedown="this.style.cursor='move';" id="popupboxHeader" class="header">
  <a onclick="hideLightbox(); return false;" href="">close</a>
  Rate Title
</div>
<div id="popupboxContent" class="content">
  {if $message}<div class="error">You must be logged in first.</div>{/if}
  <div id='loginFormWrapper'>
    <form method="post" action="{$path}/Record/{$id}/Rate" name="loginForm" onSubmit="ratingLogin('{$id}', '{$rating}', 'Record'); return false;">
      <div>      
        <input type="hidden" name="rating" id="rating" value="{$rating}" />
        <div id='haveCardLabel' class='loginFormRow'>I have a Douglas County Library Card</div>
        <div id ='loginUsernameRow' class='loginFormRow'>
          <div class='loginLabel'>{translate text='Username'}: </div>
          <div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
        </div>
        <div id ='loginPasswordRow' class='loginFormRow'>
          <div class='loginLabel'>{translate text='Password'}: </div>
          <div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
        </div>
        <div id='loginSubmitButtonRow' class='loginFormRow'>
          <input id="loginButton" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login to your account"}' />
        </div>
      </div>
    </form>
  </div>
  <div id='needACardWrapper'>
    <div id='needCardLabel' class='loginFormRow'>
    <a href="http://getacard.org">I need a Douglas County Libraries Card</a>
    </div>
    <div class='loginFormRow'>
    <a href="http://getacard.org"><img src="{$path}/interface/themes/dcl/images/library_card.gif" alt="Get a Library Card" /></a>
    </div>
  </div>
  <div id='retreiveLoginInfo'>
    <div id='forgottenCardLabel' class='loginFormRow'>
      <a href="http://getacard.org/account_lookup_form_internal.php" >FORGOTTEN YOUR CARD NUMBER?</a>
    </div>
    <div id='emailPinLabel' class='loginFormRow'>
      <a href="http://getacard.org/pin_emailer_form.php">EMAIL MY PIN</a>
    </div>
  </div>
</div>
