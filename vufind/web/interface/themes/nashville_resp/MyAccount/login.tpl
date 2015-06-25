{strip}
<div id="page-content" class="col-xs-12">
	{if $message}<p class="text-error lead">{$message|translate}</p>{/if}
	<h2>{translate text='Login to your account'}</h2>
	<div id="loginFormWrapper">
		<p class="alert alert-danger" id="loginError" style="display: none"></p>
		<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal">
			<div id="missingLoginPrompt" style="display: none">Please enter both {$usernameLabel} and {$passwordLabel}.</div>
			<div id='loginFormFields'>
				<div id ='loginUsernameRow' class='form-group'>
					<label for="username" class='control-label col-xs-12 col-sm-4'>{$usernameLabel}: </label>
					<div class='col-xs-12 col-sm-8'>
						<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control"/>
					</div>
				</div>
				<div id ='loginPasswordRow' class='form-group'>
					<label for="password" class='control-label col-xs-12 col-sm-4'>{$passwordLabel}: </label>
					<div class='col-xs-12 col-sm-8'>
						<input type="password" pattern="[0-9]*" name="password" id="password" size="28" class="form-control"/>
					</div>
				</div>
 				<div id ='loginPasswordConfirmRow' class='form-group' style="display:none">
					<label for="password2" class='control-label col-xs-12 col-sm-4'>{translate text='Confirm pin #'}: </label>
					<div class='col-xs-12 col-sm-8'>
						<input type="password" pattern="[0-9]*" name="password2" id="password2" size="28" class="form-control"/>
					</div>
				</div>
				<div id ='loginPasswordRow2' class='form-group'>
					<div class='col-xs-12 col-sm-offset-4 col-sm-8'>
						<p class='help-block'><a href="#" onclick="document.getElementById('loginPasswordConfirmRow').style.display='block';">Create new PIN</p>
						{if $showForgotPinLink}
							<p class="help-block">
								<strong><a href="{$path}/MyResearch/EmailPin">Forgot PIN?</a></strong>
							</p>
						{/if}
						{if $enableSelfRegistration == 1}
							<p class="help-block">
								<a href='{$path}/MyAccount/SelfReg'>Get a Card</a>
							</p>
						{/if}

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
					</div>
				</div>

				<div id ='loginPasswordRow2' class='form-group'>
					<div class='col-xs-12 col-sm-offset-4 col-sm-8'>
						<input type="submit" name="submit" value="Login" id="loginFormSubmit" class="btn btn-primary" onclick="return VuFind.Account.preProcessLogin();"/>
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

			</div>
		</form>
	</div>
</div>
{/strip}
{literal}
	<script type="text/javascript">
		$(document).ready(function (){
			var haslocalStorage = false;
			if ("localStorage" in window) {
				try {
					window.localStorage.setItem('_tmptest', 'temp');
					haslocalStorage = (window.localStorage.getItem('_tmptest') == 'temp');
					// if we get the same info back, we are good. Otherwise, we don't have localStorage.
					window.localStorage.removeItem('_tmptest');
				} catch(error) {} // something failed, so we don't have localStorage available.
			}

			if (haslocalStorage) {
				var rememberMe = (window.localStorage.getItem('rememberMe') == 'true'); // localStorage saves everything as strings
				if (rememberMe) {
					var lastUserName = window.localStorage.getItem('lastUserName'),
									lastPwd = window.localStorage.getItem('lastPwd');
//							showPwd = (window.localStorage.getItem('showPwd') == 'true'); // localStorage saves everything as strings
					$("#username").val(lastUserName);
					$("#password").val(lastPwd);
//					$("#showPwd").prop("checked", showPwd  ? "checked" : '');
//					if (showPwd) VuFind.pwdToText('password');
				}
				$("#rememberMe").prop("checked", rememberMe ? "checked" : '');
			} else {
				// disable, uncheck & hide RememberMe checkbox if localStorage isn't available.
				$("#rememberMe").prop({checked : '', disabled: true}).parent().hide();
			}
			// Once Box is shown, focus on username input and Select the text;
			$("#modalDialog").on('shown.bs.modal', function(){
				$('#username').focus().select();
			})
		});
	</script>
{/literal}
