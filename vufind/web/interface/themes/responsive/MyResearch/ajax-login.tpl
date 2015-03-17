<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h4 class="modal-title" id="myModalLabel">Login</h4>
</div>
<div class="modal-body">
	<p class="alert alert-danger" id="loginError" style="display: none"></p>
	<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal" role="form" onsubmit="return VuFind.Account.processAjaxLogin()">
		<div id="missingLoginPrompt" style="display: none">Please enter both {$usernameLabel} and {$passwordLabel}.</div>
		<div id ='loginUsernameRow' class='form-group'>
			<label for="username" class='control-label col-xs-12 col-sm-4'>{$usernameLabel}:</label>
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
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<span class="modal-buttons">
		<input type="submit" name="submit" value="{if $multistep}Continue{else}Login{/if}" id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return VuFind.Account.processAjaxLogin()"/>
	</span>
</div>
{literal}
<script type="text/javascript">
	$('#username').focus().select();
	$(document).ready(
		function (){
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
		}
	);
</script>
{/literal}