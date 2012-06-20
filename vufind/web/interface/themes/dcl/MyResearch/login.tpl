{strip}
<div id="page-content" class="content">
	{if $message}<div class="error">{$message|translate}</div>{/if}
	<div class="resulthead">
		<h3>{translate text='Login to your account'}</h3>
	</div>
	<div id='loginFormWrapper'>
		<form method="post" action="{$path}/MyResearch/Home" id="loginForm">
			<div id='loginFormFields'>
				<div id='haveCardLabel' class='loginFormRow'>I have a Douglas County Library Card</div>
				<div id ='loginUsernameRow' class='loginFormRow'>
					<div class='loginLabel'>{translate text='Username'}: </div>
					<div class='loginField'><input type="text" name="username" id="username" value="{$username|escape}" size="15"/></div>
				</div>
				<div id ='loginPasswordRow' class='loginFormRow'>
					<div class='loginLabel'>{translate text='Password'}: </div>
					<div class='loginField'>
						<input type="password" name="password" id="password" size="15"/>
					</div>
				</div>
				{*
				<div id ='loginPasswordRow2' class='loginFormRow'>
					<div class='loginLabel'>&nbsp;</div>
					<div class='loginField'>
						&nbsp;<input type="checkbox" id="showPwd" name="showPwd" onclick="return pwdToText('password')"/><label for="showPwd">{translate text="Reveal Password"}</label>
					</div>
				</div>
				*}
				{if !$inLibrary}
				<div id ='loginPasswordRow3' class='loginFormRow'>
					<div class='loginLabel'>&nbsp;</div>
					<div class='loginField'>
						<input type="checkbox" id="rememberMe" name="rememberMe"/><label for="rememberMe">{translate text="Remember Me"}</label>
					</div>
				</div>
				{/if}
				<div id='loginSubmitButtonRow' class='loginFormRow'>
					<input id="loginButton" type="image" name="submit" value="Login" src='{$path}/interface/themes/default/images/login.png' alt='{translate text="Login to your account"}' />
					{if $followup}<input type="hidden" name="followup" value="{$followup}"/>{/if}
        	{if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}"/>{/if}
        	{if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}"/>{/if}
        	{if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}"/>{/if}
        	{if $comment}<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>{/if}
					{if $returnUrl}<input type="hidden" name="returnUrl" value="{$returnUrl}"/>{/if}
  
					{if $comment}
						<input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
					{/if}
				</div>
			</div>
		</form>
	</div>
	<div id='needACardWrapper'>
		<div id='needCardLabel' class='loginFormRow'>
			<a href="http://getacard.org">I need a Douglas County Library Card</a>
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
	<script type="text/javascript">$('#username').focus();</script>
</div>
{/strip}