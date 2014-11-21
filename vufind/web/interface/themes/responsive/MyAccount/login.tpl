{strip}
<div id="page-content" class="col-xs-12">
	{if $message}<p class="text-error lead">{$message|translate}</p>{/if}
	<h2>{translate text='Login to your account'}</h2>
	<div id="loginFormWrapper">
		<p class="alert alert-danger" id="loginError" style="display: none"></p>
		<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal">
			<div id='loginFormFields'>
				<div id ='loginUsernameRow' class='form-group'>
					<label for="username" class='control-label col-xs-12 col-sm-4'>{$usernameLabel}</label>
					<div class='col-xs-12 col-sm-8'>
						<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control"/>
					</div>
				</div>
				<div id ='loginPasswordRow' class='form-group'>
					<label for="password" class='control-label col-xs-12 col-sm-4'>{$passwordLabel}: </label>
					<div class='col-xs-12 col-sm-8'>
						<input type="password" name="password" id="password" size="28" onkeypress="return VuFind.submitOnEnter(event, '#loginForm');" class="form-control"/>

						{if $enableSelfRegistration == 1}
							<p class="help-block">
								Don't have a library card?  <a href='{$path}/MyAccount/SelfReg'>Register for a new Library Card</a>.
							</p>
						{/if}
					</div>
				</div>
				<div id ='loginPasswordRow2' class='form-group'>
					<div class='col-xs-12 col-sm-offset-4 col-sm-8'>
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
	<script type="text/javascript">$('#username').focus();</script>
</div>
{/strip}
{literal}
	<script type="text/javascript">
		$(document).ready(
			function (){
				var rememberMe = true;
				if (localStorage.lastUserName && localStorage.lastUserName != ""){
					$("#username").val(localStorage.lastUserName);
				}else{
					rememberMe = false;
				}
				if (localStorage.lastPwd && localStorage.lastPwd != ""){
					$("#password").val(localStorage.lastPwd);
				}else{
					rememberMe = false;
				}
				if (rememberMe){
					$("#rememberMe").prop("checked", "checked");
				}
			}
		);
	</script>
{/literal}