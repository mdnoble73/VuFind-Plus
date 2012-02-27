{if $holdingsSummary.status == 'Available At'}
  <div class="availability"><strong>Locations:</strong> <span class='locations'>{$holdingsSummary.availableAt}</span> </div>
{elseif $holdingsSummary.status != 'Available online'}
  <div class="availability"><a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if strlen($holdingsSummary.unavailableStatus) > 0 && ($holdingsSummary.class == 'checkedOut') && ($holdingsSummary.statusfull != $holdingsSummary.unavailableStatus) }({translate text=$holdingsSummary.unavailableStatus}){/if}</a></div>
{/if}
{if $holdingsSummary.callnumber}
  <div><strong>Listed under:</strong> <span class="callnumber"><a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a></span></div>
{/if}
{if $holdingsSummary.isDownloadable}
  <div>Available Online from <a href='{$holdingsSummary.downloadLink}' {if !(isset($holdingsSummary.localDownload) || $holdingsSummary.localDownload == false )}target='_blank'{/if}>{$holdingsSummary.downloadText}</a></div>
{else}
  <div class="copies-summary fine-print">
    {$holdingsSummary.numCopies} total {if $holdingsSummary.numCopies == 1}copy{else}copies{/if},
    {$holdingsSummary.availableCopies} on shelf.
    {if $holdingsSummary.holdQueueLength >= 0}
      {$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person{else}people{/if} on the wait list.
    {/if}
    {if $holdingsSummary.numCopiesOnOrder > 0}
      {$holdingsSummary.numCopiesOnOrder} copies are on order.
    {/if}
  </div>
{/if}
