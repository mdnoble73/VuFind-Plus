<div data-role="dialog">
	<div data-role="header" data-theme="d" data-position="inline">
		<h1>
			{translate text="Login to your account"}
		</h1>
	</div>

	<div data-role="content">
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
					{if !$inLibrary}
					<div id ='loginPasswordRow3' class='loginFormRow'>
						<div class='loginLabel'>&nbsp;</div>
						<div class='loginField'>
							<input type="checkbox" id="rememberMe" name="rememberMe"/><label for="rememberMe">{translate text="Remember Me"}</label>
						</div>
					</div>
					{/if}
					<div id='loginSubmitButtonRow' class='loginFormRow'>
						<a href="#" onclick="return processAjaxLogin();" data-role="button">Login</a>
						{if $comment}
							<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
						{/if}
					</div>
				</div>
			</form>
		</div>
		<script type="text/javascript">$('#username').focus();</script>
	</div>
</div>