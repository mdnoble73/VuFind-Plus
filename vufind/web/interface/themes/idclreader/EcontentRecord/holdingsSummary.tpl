<ul data-role="listview" id='SummaryEbook'>
	<li data-role="list-divider">Summary</li>
	<li>{$holdingsSummary.status}</li>
	{if $holdingsSummary.numHoldings == 0}
			<li>No copies available yet.</li>
			<li>{$holdingsSummary.wishListSize} {if $holdingsSummary.wishListSize == 1}person has{else}people have{/if} added the record to their wish list.</li>
	{else}
		{if strcasecmp($holdingsSummary.source, 'OverDrive') == 0}
			<li>Available for use from OverDrive.</li>
		{elseif $holdingsSummary.source == 'Freegal'}
			<li>Downloadable from Freegal.</li>
		{elseif $holdingsSummary.accessType == 'free'}
			<li>Available for multiple simultaneous usage.</li>
		{elseif $holdingsSummary.onHold}
			<li>You are number {$holdingsSummary.holdPosition} on the wait list.</li>
		{elseif $holdingsSummary.checkedOut}
			<li>{* Don't need to view copy information for checked out items *}</li>
		{else}
			<li>{$holdingsSummary.totalCopies} total {if $holdingsSummary.totalCopies == 1}copy{else}copies{/if},</li>
			<li>{$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} available.</li>
			{if $holdingsSummary.onOrderCopies > 0}
				<li>{$holdingsSummary.onOrderCopies} {if $holdingsSummary.onOrderCopies == 1}is{else}are{/if} on order.</li> 
			{/if}
		{/if}
		{if $holdingsSummary.numHolds >= 0}
			<li>{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.</li>
		{/if}
	{/if} 
</ul>