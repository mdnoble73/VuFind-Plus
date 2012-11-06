<div id = "holdingsEContentSummary{$id}" class="holdingsSummary {$holdingsSummary.class}">
	<div class="availability holdingsSummaryStatusLine {$holdingsSummary.class}">
		{$holdingsSummary.status}
	</div>

	
	{if $holdingsSummary.numHoldings == 0}
		<div class="holdableCopiesSummary">
			No copies are available yet.
		</div>
		<div class='holdableCopiesSummary2'>
			{$holdingsSummary.wishListSize} {if $holdingsSummary.wishListSize == 1}person has{else}people have{/if} added this title to their wish list.
		</div>
	{else}
		<div class="holdableCopiesSummary">
			{if $holdingsSummary.source == 'Freegal'}
				Downloadable from Freegal.
			{elseif $holdingsSummary.accessType == 'free'}
				Available for multiple simultaneous usage. 
			{elseif $holdingsSummary.onHold}
				You are number {$holdingsSummary.holdPosition} on the wait list.
			{elseif $holdingsSummary.checkedOut}
				{* Don't need to view copy information for checked out items *}
			{elseif $holdingsSummary.accessType == 'external' && !$holdingsSummary.isOverDrive}
				{$holdingsSummary.totalCopies} total {if $holdingsSummary.totalCopies == 1}copy{else}copies{/if}.
			{elseif $holdingsSummary.alwaysAvailable == 'true' && $holdingsSummary.isOverDrive}
				Always Available.
			{else}
				{$holdingsSummary.totalCopies} total {if $holdingsSummary.totalCopies == 1}copy{else}copies{/if}, 
				{$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} available. 
				{if $holdingsSummary.onOrderCopies > 0}
					{$holdingsSummary.onOrderCopies} {if $holdingsSummary.onOrderCopies == 1}is{else}are{/if} on order. 
				{/if}
			{/if}
		</div>
		{if is_numeric($holdingsSummary.holdQueueLength) && $holdingsSummary.holdQueueLength >= 0 && !$holdingsSummary.alwaysAvailable}
			<div class='holdableCopiesSummary2'>
				{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.
			</div>
		{/if}
	{/if} 
	
	{if $showOtherEditionsPopup}
		<div class="otherEditions">
			<a href="#" onclick="loadOtherEditionSummaries('{$holdingsSummary.recordId}', true)">Other Formats and Languages</a>
		</div>
	{/if}
 </div>