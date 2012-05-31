<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
  <a onclick="hideLightbox(); return false;" href="">close</a>
  {translate text='Login to your account'}
</div>
<div id="popupboxContent" class="content">
  <div id='ajaxLoginForm'>
    <form method="post" action="{$path}/MyResearch/Home" id="loginForm">
      <div id='loginFormFields'>
        <div id ='loginUsernameRow' class='loginFormRow'>
          <div class='loginLabel'>{translate text='Username'}: </div>
          <div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
        </div>
        <div id ='loginPasswordRow' class='loginFormRow'>
          <div class='loginLabel'>{translate text='Password'}: </div>
          <div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
        </div>
        {if !$inLibrary}
        <div id ='loginPasswordRow3' class='loginFormRow'>
          <div class='loginLabel'>&nbsp;</div>
          <div class='loginField'>
            <input type="checkbox" id="rememberMe" name="rememberMe"/>&nbsp;<label for="rememberMe">{translate text="Remember Me"}</label>
          </div>
        </div>
        {/if}
        <div id='loginSubmitButtonRow' class='loginFormRow'>
          <input type="button" onclick="return processAjaxLogin();" id="loginButton" name="submit" value="Login" />
          {if $comment}
            <input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
          {/if}
        </div>
      </div>
    </form>
  </div>
  <script type="text/javascript">$('#username').focus();</script>
</div>
