<div id="page-content" class="content">
	<div id="main-content">
		<div class="resulthead"><h3>{translate text='Registration Results'}</h3></div>
		<div class="page">
		{if $registrationResult.success}
			<p>
			Here is your temporary barcode to use for future authentication:&nbsp;{$registrationResult.barcode}. 
			</p>
			<p>
			To receive your permanent card, you will need to bring a picture ID to the library. 
			</p>
			<form method="post" action="{$url}/MyResearch/Home" id="loginForm">
				<div id='loginFormFields'>
					<div id='haveCardLabel' class='loginFormRow'>Login now</div>
					<div id ='loginUsernameRow' class='loginFormRow'>
						<div class='loginLabel'>{translate text='Username'}: </div>
						<div class='loginField'><input type="text" name="username" id="username" value="{$registrationResult.barcode|escape}" size="15"/></div>
					</div>
					<div id ='loginPasswordRow' class='loginFormRow'>
						<div class='loginLabel'>{translate text='Password'}: </div>
						<div class='loginField'><input type="password" name="password" size="15"/></div>
					</div>
					<div id='loginSubmitButtonRow' class='loginFormRow'>
						<input id="loginButton" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login to your account"}' />
					</div>
				</div>
			</form>
		{else}
			<p>Sorry, we could not process your registration.  Please visit our local library to register for a library card.</p> 
		{/if}
		</div>
	</div>
</div>
