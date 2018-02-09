{strip}
		{if $hooplaUser}{* Linked User that is not the main user *}
			<p>
				Using card for {$hooplaUser->getNameAndLibraryLabel()} :
			</p>
		{/if}
	{if $hooplaPatronStatus}
		<div class="alert alert-info">
			You have <span class="badge">{$hooplaPatronStatus->borrowsRemaining}</span> of {$hooplaPatronStatus->borrowsAllowedPerMonth} Hoopla check outs available this month. Proceed with checkout?
		</div>
		<br>
		<div class="form-group">
			<label for="stopHooplaConfirmation" class="checkbox"><input type="checkbox" name="stopHooplaConfirmation" id="stopHooplaConfirmation"> Don't ask again. <small>(This can be changed under your Account Settings)</small></label>
		</div>
	{else}
		<div class="alert alert-info">
			You haven't created an account at Hoopla yet. Would you like to do so now?
		</div>
	{/if}
{/strip}