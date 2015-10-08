{strip}
	<div id="main-content">
		{if $profile->web_note}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
			</div>
		{/if}

		{if $profile->getNumHoldsAvailableTotal() && $profile->getNumHoldsAvailableTotal() > 0}
			<div class="text-info text-center alert alert-info"><a href="/MyAccount/Holds" class="alert-link">You have {$profile->getNumHoldsAvailableTotal()} holds ready for pick up.</a></div>
		{/if}

		{* Internal Grid *}
		<h2 class="myAccountTitle">{translate text='Recommended for you'}</h2>

		{foreach from=$resourceList item=suggestion name=recordLoop}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{$suggestion}
			</div>
		{foreachelse}
			<div class="error">You have not rated any titles.  Please rate some titles so we can display suggestions for you. </div>
		{/foreach}
	</div>
{/strip}