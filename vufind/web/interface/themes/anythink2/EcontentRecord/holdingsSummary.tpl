<div id="holdingsSummary" class="holdingsSummary">
  <h3 class="available">{$holdingsSummary.status}</h3>
  <div class="holdableCopiesSummary">
    {if $holdingsSummary.numHoldings == 0}
      <p>No copies available yet.</p>
      <div class="fine-print">{$holdingsSummary.wishListSize} {if $holdingsSummary.wishListSize == 1}person has{else}people have{/if} added the record to their wish list.</div>
    {else}
      {if strcasecmp($holdingsSummary.source, 'OverDrive') == 0}
        <p>Available for use from OverDrive.</p>
      {elseif $holdingsSummary.source == 'Freegal'}
        <p>Downloadable from Freegal.</p>
      {elseif $holdingsSummary.accessType == 'free'}
        <p>Available for multiple simultaneous usage. </p>
      {elseif $holdingsSummary.onHold}
        <p>You are number {$holdingsSummary.holdPosition} on the wait list.</p>
      {elseif $holdingsSummary.checkedOut}
        {* Don't need to view copy information for checked out items *}
      {else}
        <div class="fine-print">
        {$holdingsSummary.totalCopies} {if $holdingsSummary.totalCopies == 1}copy{else}copies{/if}, 
        {$holdingsSummary.availableCopies} available. 
        {if $holdingsSummary.numHolds >= 0}
          {$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person{else}people{/if} on the wait list.
        {/if}
        {if $holdingsSummary.onOrderCopies > 0}
          {$holdingsSummary.onOrderCopies} copies on order. 
        {/if}
        </div>
      {/if}
    {/if} 
    {if $showOtherEditionsPopup}
      <a href="#" onclick="loadOtherEditionSummariesAnythink('{$holdingsSummary.recordId}', true)">Other Formats and Languages</a>
    {/if}
  </div>
</div>
 