{strip}
	<p class="alert alert-danger" id="loginError" style="display: none"></p>
	<p class="alert alert-danger" id="cookiesError" style="display: none">It appears that you do not have cookies enabled on this computer.  Cookies are required to access account information.</p>
	<p class="alert alert-info" id="loading" style="display: none">
		Logging you in now. Please wait.
	</p>
<form method="post" {*action="{$path}/MyAccount/Home"*} id="masqueradeForm" class="form-horizontal" role="form" {*onsubmit="return VuFind.Account.processAjaxLogin()"*}>
	<div id="loginUsernameRow" class="form-group">
		<label for="cardNumber" class="control-label col-xs-12 col-sm-4">{translate text="Library Card Number"}:</label>
		<div class="col-xs-12 col-sm-8">
			<input type="text" name="cardNumber" id="cardNumber" value="{$cardNumber|escape}" size="28" class="form-control required">
		</div>
	</div>
</form>
	<script type="text/javascript">
		$('#cardNumber').focus().select();
		{literal}
		$("#masqueradeForm").validate({
			submitHandler: function(){
				VuFind.Account.initiateMasquerade();
			}
		});
		{/literal}

	</script>
{/strip}