{strip}
{if $statusInformation.availableHere}
	{if $statusInformation.availableOnline}
		<div class="related-manifestation-shelf-status available">Available Online</div>
	{elseif $statusInformation.allLibraryUseOnly}
		<div class="related-manifestation-shelf-status available">It's Here (library use only)</div>
	{else}
		{if $showItsHere}
			<div class="related-manifestation-shelf-status available">It's Here</div>
		{else}
			<div class="related-manifestation-shelf-status available">On Shelf</div>
		{/if}
	{/if}
{elseif $statusInformation.availableLocally}
	{if $statusInformation.availableOnline}
		<div class="related-manifestation-shelf-status available">Available Online</div>
	{elseif $statusInformation.allLibraryUseOnly}
		<div class="related-manifestation-shelf-status available">On Shelf (library use only)</div>
	{elseif $onInternalIP}
		<div class="related-manifestation-shelf-status availableOther">Available at another branch</div>
	{else}
		<div class="related-manifestation-shelf-status available">On Shelf</div>
	{/if}
{elseif $statusInformation.availableOnline}
	<div class="related-manifestation-shelf-status available">Available Online</div>
{elseif $statusInformation.inLibraryUseOnly}
	<div class="related-manifestation-shelf-status available">In Library Use Only</div>
{elseif $statusInformation.available && $statusInformation.hasLocalItem}
	<div class="related-manifestation-shelf-status availableOther">Checked Out/Available Elsewhere</div>
{elseif $statusInformation.available}
	<div class="related-manifestation-shelf-status availableOther">{translate text='Available from another library'}</div>
{else}
	<div class="related-manifestation-shelf-status checked_out">
		{if $statusInformation.groupedStatus}{$statusInformation.groupedStatus}{else}Checked Out{/if}
	</div>
{/if}
{if $statusInformation.numHolds > 0 || $statusInformation.onOrderCopies > 0}
	<div class="smallText">
		{if $statusInformation.numHolds > 0}
			{$statusInformation.numHolds} {if $statusInformation.numHolds == 1}person is{else}people are{/if} on the wait list
			{if $statusInformation.onOrderCopies > 0}, {else}.{/if}
		{/if}
		{if $statusInformation.onOrderCopies > 0}
			{$statusInformation.onOrderCopies} {if $statusInformation.onOrderCopies == 1}copy{else}copies{/if} on order.
		{/if}
	</div>
{/if}
{/strip}