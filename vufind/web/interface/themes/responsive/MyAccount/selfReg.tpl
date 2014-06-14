<h3>{translate text='Register for a Library Card'}</h3>
<div class="page">
	<div id='selfRegDescription' class="alert alert-info">This page allows you to register as a patron of our library online. You will have limited privileges initially.</div>
	<div id='selfRegistrationFormContainer'>
		{if (isset($selfRegResult) && $selfRegResult.success)}
			<div id='selfRegSuccess' class="alert alert-success">
				Congratulations, you have successfully registered for a new library card.
				Your library card number is <strong>{$selfRegResult.barcode}</strong>.
				You will have limited privileges.
			</div>
		{else}
			<form id='selfRegistrationForm' name='selfRegistrationForm' action='{$path}/MyAccount/SelfReg' method="post" class="form form-horizontal">
				<div>
					{if (isset($selfRegResult) && !$selfRegResult.success)}
						<div id='selfRegFailure' class="alert alert-danger">
							Sorry, we could not create register a library card for you with that information. Please visit your local library to get a card.
						</div>
					{/if}
					<div class='form-group'>
						<label for='firstName' class="control-label col-sm-2">First Name</label>
						<div class="col-sm-10">
							<input id='firstName' name='firstName' type='text' maxlength="40" size="40" class='required form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<label for='lastName' class="control-label col-sm-2">Last Name</label>
						<div class="col-sm-10">
							<input id='lastName' name='lastName' type='text' maxlength="60" size="40" class='required form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<label for='address' class="control-label col-sm-2">Address</label>
						<div class="col-sm-10">
							<input id='address' name='address' type='text' maxlength="128" size="40" class='required form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<label for='city' class="control-label col-sm-2">City</label>
						<div class="col-sm-10">
							<input id='city' name='city' type='text' maxlength="48" size="20" class='required form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<label for='state' class="control-label col-sm-2">State</label>
						<div class="col-sm-10">
							<input id='state' name='state' type='text' maxlength="32" size="20" class='required form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<label for='zip' class="control-label col-sm-2">Zip Code</label>
						<div class="col-sm-10">
							<input id='zip' name='zip' type='text' maxlength="32" size="20" class='required form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<label for='state' class="control-label col-sm-2">E-Mail</label>
						<div class="col-sm-10">
							<input id='email' name='email' type='text' maxlength="128" size="40" class='email form-control'/>
						</div>
					</div>
					<div class='form-group'>
						<div class="col-sm-offset-2 col-sm-10">
							<input type="submit" name="submit" value="Submit" class="btn btn-primary"/>
						</div>
					</div>
					<script type="text/javascript">{literal}
						$(document).ready(function () {
							$("#selfRegistrationForm").validate();
						});
						{/literal}
					</script>
				</div>
			</form>
		{/if}
	</div>

</div>
<b class="bbot"><b></b></b>
</div>
</div>
</div>
