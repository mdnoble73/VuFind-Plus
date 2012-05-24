<div id = "holdingsSummary" class="holdingsSummary">
	<div class="availability">
		{$holdingsSummary.status}
	</div>

	<div class="holdableCopiesSummary">
		{if $holdingsSummary.numHoldings == 0}
			No copies available yet.
			<br/>{$holdingsSummary.wishListSize} {if $holdingsSummary.wishListSize == 1}person has{else}people have{/if} added the record to their wish list.
		{else}
			{if strcasecmp($holdingsSummary.source, 'OverDrive') == 0}
				Available for use from OverDrive.
			{elseif $holdingsSummary.source == 'Freegal'}
				Downloadable from Freegal.
			{elseif $holdingsSummary.accessType == 'free'}
				Available for multiple simultaneous usage. 
			{elseif $holdingsSummary.onHold}
				You are number {$holdingsSummary.holdPosition} on the wait list.
			{elseif $holdingsSummary.checkedOut}
				{* Don't need to view copy information for checked out items *}
			{else}
				{$holdingsSummary.totalCopies} total {if $holdingsSummary.totalCopies == 1}copy{else}copies{/if}, 
				{$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} available. 
				{if $holdingsSummary.onOrderCopies > 0}
					{$holdingsSummary.onOrderCopies} {if $holdingsSummary.onOrderCopies == 1}is{else}are{/if} on order. 
				{/if}
			{/if}
			{if $holdingsSummary.numHolds >= 0}
				<br/>{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.
			{/if}
		{/if} 
		{if $showOtherEditionsPopup}
		<div class="otherEditions">
			<a href="#" onclick="loadOtherEditionSummaries('{$holdingsSummary.recordId}', true)">Other Formats and Languages</a>
		</div>
		{/if}
	</div>
     
 </div>