<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h4 class="modal-title" id="myModalLabel">Login</h4>
</div>
<div class="modal-body">
	<p class="alert alert-danger" id="loginError" style="display: none"></p>
	<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal" role="form" onsubmit="return VuFind.Account.processAjaxLogin()">
		<div id ='loginUsernameRow' class='form-group'>
			<label for="username" class='control-label col-xs-12 col-sm-4'>{translate text='Username'}</label>
			<div class='col-xs-12 col-sm-8'>
				<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control"/>
			</div>
		</div>
		<div id ='loginPasswordRow' class='form-group'>
			<label for="password" class='control-label col-xs-12 col-sm-4'>{translate text='Password'}: </label>
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


	<script type="text/javascript">$('#username').focus().select();</script>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<span class="modal-buttons">
		<input type="submit" name="submit" value="{if $multistep}Continue{else}Login{/if}" id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return VuFind.Account.processAjaxLogin()"/>
	</span>
</div>
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