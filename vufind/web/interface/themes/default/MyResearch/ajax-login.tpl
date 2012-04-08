<div id="page-content" class="content">
	{if $message}<div class="error">{$message|translate}</div>{/if}
	<div class="resulthead">
	<h3>{translate text='Login to your account'}</h3>
	</div>
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
				<div id ='loginPasswordRow2' class='loginFormRow'>
					<div class='loginLabel'>&nbsp;</div>
					<div class='loginField'>
						<input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label>
					</div>
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
	<script type="text/javascript">$('#username').focus();</script>
</div>