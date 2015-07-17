{strip}
	{if $profile.numHoldsAvailableTotal && $profile.numHoldsAvailableTotal > 0}
		<div class="text-info text-center alert alert-info">
			{if !$noLink}<a href="/MyAccount/Holds" class="alert-link">{/if}
			You have <span style="font-weight: bold">{$profile.numHoldsAvailableTotal}</span> hold{if $profile.numHoldsAvailableTotal !=1}s{/if} ready for pick up.
		{if !$noLink}</a>{/if}
		</div>
	{/if}
{/strip}
