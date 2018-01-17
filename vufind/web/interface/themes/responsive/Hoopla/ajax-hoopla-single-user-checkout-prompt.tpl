{strip}
	{if $hooplaPatronStatus}
		{if $hooplaUser}{* Linked User that is not the main user *}
			<p>
				Using card for {$hooplaUser->getNameAndLibraryLabel()} :
			</p>
		{/if}
		<div class="alert alert-info">
			You have <span class="badge">{$hooplaPatronStatus->borrowsRemaining}</span> of {$hooplaPatronStatus->borrowsAllowedPerMonth} Hoopla check outs available this month. Proceed with checkout?
		</div>
	{else}
		{*TODO: go to hoopla registration page?? *}
	{/if}
{/strip}