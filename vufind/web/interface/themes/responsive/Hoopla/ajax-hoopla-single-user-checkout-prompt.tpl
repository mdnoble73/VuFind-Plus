{strip}
		{if $hooplaUser}{* Linked User that is not the main user *}
			<p>
				Using card for {$hooplaUser->getNameAndLibraryLabel()} :
			</p>
		{/if}
		<div class="alert alert-info">
	{if $hooplaPatronStatus}
			You have <span class="badge">{$hooplaPatronStatus->borrowsRemaining}</span> of {$hooplaPatronStatus->borrowsAllowedPerMonth} Hoopla check outs available this month. Proceed with checkout?
	{else}
			You haven't created an account at Hoopla yet. Would you like to do so now?
	{/if}
		</div>
{/strip}