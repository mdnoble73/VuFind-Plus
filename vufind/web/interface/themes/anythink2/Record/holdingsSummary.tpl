<div id="holdingsSummary" class="holdingsSummary">
  {* Don't show status for non-drm EPUB files*}
  {if $holdingsSummary.status == 'Available At'}
    <h3 class="available">Available</h3>
    <div>Locations: <span class='availableAtList'>{$holdingsSummary.availableAt}</span></div>
  {elseif $holdingsSummary.status != 'Available online'}
  <!-- <h3><a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status}</a></h3> -->
  <h3>{translate text=$holdingsSummary.status}</h3>
  {/if}
  {if $holdingsSummary.callnumber}
    <div class='callNumber'>
      <!-- Listed under <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a> -->
      Listed under <span class="listed">{$holdingsSummary.callnumber}</span>
    </div>
  {/if}
  {if $holdingsSummary.isDownloadable}
    <p>Available Online from <a href='{$holdingsSummary.downloadLink}' {if !(isset($holdingsSummary.localDownload) || $holdingsSummary.localDownload == false )}target='_blank'{/if}>{$holdingsSummary.downloadText}</a></p>
  {else}
    <div class="holdableCopiesSummary">
      <span class="fine-print">
      {$holdingsSummary.numCopies} {if $holdingsSummary.numCopies == 1}copy{else}copies{/if},
      {$holdingsSummary.availableCopies} on shelf.
      {if $holdingsSummary.holdQueueLength >= 0}
        {$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person{else}people{/if} on the wait list.
      {/if}
      {if $holdingsSummary.numCopiesOnOrder > 0}
        {$holdingsSummary.numCopiesOnOrder} copies on order.
      {/if}
      {if $showOtherEditionsPopup}
        <a href="#" onclick="loadOtherEditionSummariesAnythink('{$holdingsSummary.recordId}', false)">Other Formats and Languages</a>
      {/if}</span>
    </div>
  {/if}
</div>
