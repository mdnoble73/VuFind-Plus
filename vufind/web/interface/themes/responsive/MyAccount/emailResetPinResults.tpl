{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resulthead"><h3>{translate text='Reset My PIN'}</h3></div>
			<div class="page">
				{if $emailResult.error}
					<p class="alert alert-danger">{$emailResult.error}</p>
					<div>
						<a class="btn btn-primary" role="button" href="{$path}/MyAccount/EmailResetPin">Try Again</a>
					</div>
				{else}
					<p class="alert alert-success">
						A email has been sent to the email address on file for your account containing a link to reset your PIN.
					</p>
					<p>
						<a class="btn btn-primary" role="button" href="{$path}/MyAccount/Login">{translate text='Login'}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}