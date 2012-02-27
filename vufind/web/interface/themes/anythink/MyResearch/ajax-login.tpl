<div id="page-content" class="content">
   {if $message}<div class="error">{$message|translate}</div>{/if}
   <div class="resulthead">
   <h3>{translate text='Login to your account'}</h3>
   </div>
   <div id='loginFormWrapper'>
	   <form method="post" action="{$path}/MyResearch/Home" id="loginForm">
	   	 <div id='loginFormFields'>
		   <div id='haveCardLabel' class='loginFormRow'>I have a {$libraryName} Card</div>
	       <div id ='loginUsernameRow' class='loginFormRow'>
	       	 <div class='loginLabel'>{translate text='Username'}: </div>
	         <div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
	       </div>
	       <div id ='loginPasswordRow' class='loginFormRow'>
	       	 <div class='loginLabel'>{translate text='Password'}: </div>
	         <div class='loginField'><input type="password" name="password" id="password" size="15"/></div>
	       </div>
	       <div id='loginSubmitButtonRow' class='loginFormRow'>
	     	 <input onclick="return processAjaxLogin()" id="loginButton" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login to your account"}' />
	     	    
	       {if $comment}
	         <input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
	       {/if}
	      </div>
	    </div>
	  </form>
  </div>
  <div id='needACardWrapper'>
		<div id='needCardLabel' class='loginFormRow'>
		<a href="{$path}/MyResearch/GetCard">I need an {$libraryName} Card</a>
		</div>
		<div class='loginFormRow'>
		<a href="{$path}/MyResearch/GetCard"><img border="0" src="{$path}/interface/themes/{$theme}/images/library_card.jpg"></a>
		</div>
	</div>
	<div id='retreiveLoginInfo'>
		<div id='emailPinLabel' class='loginFormRow'>
			<a href="{$path}/MyResearch/EmailPin">EMAIL MY PIN</a>
		</div>
	</div>
  <script type="text/javascript">$('#username').focus();</script>
</div>

