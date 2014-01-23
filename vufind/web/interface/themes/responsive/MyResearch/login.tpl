{strip}
<div id="page-content" class="content">
	{if $message}<p class="text-error lead">{$message|translate}</p>{/if}
	<div class="resulthead">
		<h3>{translate text='Login to your account'}</h3>
	</div>
	<div id="loginFormWrapper">
		<form method="post" action="{$path}/MyResearch/Home" id="loginForm" class="form-horizontal">
			<div id='loginFormFields'>
				<div id ='loginUsernameRow' class='form-group'>
					<label for="username" class='control-label'>{translate text='Username'}: </label>
					<div class='controls'><input type="text" name="username" id="username" value="{$username|escape}" size="28"/></div>
				</div>
				<div id ='loginPasswordRow' class='form-group'>
					<label for="password" class='control-label'>{translate text='Password'}: </label>
					<div class='controls'>
						<input type="password" name="password" id="password" size="28"/>
					</div>
				</div>
				<div id ='loginPasswordRow2' class='form-group'>
					<div class='controls'>
						<label for="showPwd" class="checkbox">
							<input type="checkbox" id="showPwd" name="showPwd" onclick="return VuFind.pwdToText('password')"/>
							{translate text="Reveal Password"}
						</label>

						{if !$inLibrary}
							<label for="rememberMe" class="checkbox">
								<input type="checkbox" id="rememberMe" name="rememberMe"/>
								{translate text="Remember Me"}
							</label>
						{/if}

						<input type="submit" name="submit" value="Login" id="loginFormSubmit" class="btn"/>
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

				{if $enableSelfRegistration == 1}
					<a href='{$path}/MyResearch/SelfReg'>Register for a new Library Card</a>
				{/if}

			</div>
		</form>
	</div>
	<script type="text/javascript">$('#username').focus();</script>
</div>
{/strip}