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
			{if (isset($selfRegResult))}
				<div id="selfRegFail" class="alert alert-warning">
					Sorry, we were unable to create a library card for you.  You may already have an account or there may be an error with the information you entered.
					Please try again or visit the library in person so we can create a card for you.
				</div>
			{/if}
			{$selfRegForm}
		{/if}
	</div>

</div>

