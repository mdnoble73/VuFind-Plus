{strip}
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
				{if $holdingsSummary.onHold}
					You are number {$holdingsSummary.holdPosition} on the wait list.
				{elseif $holdingsSummary.checkedOut}
				{* Don't need to view copy information for checked out items *}
				{elseif $holdingsSummary.alwaysAvailable == 'true'}
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
	</div>
{/strip}