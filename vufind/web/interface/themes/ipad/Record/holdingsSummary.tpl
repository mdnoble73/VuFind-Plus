<div id = "holdingsSummary" class="holdingsSummary">
  {if $holdingsSummary.callnumber}
    <div class='callNumber'>
      Shelved at <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a>
    </div>
  {/if}
  {* Don't show status for non-drm EPUB files*} 
  {if $holdingsSummary.status == 'Available At'}
    <div class="availability">
      Now Available: <span class='availableAtList'>{$holdingsSummary.availableAt}</span> 
    </div>
      
  {elseif $holdingsSummary.status != 'Available online'}
    <div class="availability">
      <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if strlen($holdingsSummary.unavailableStatus) > 0 && ($holdingsSummary.class == 'checkedOut') && ($holdingsSummary.statusfull != $holdingsSummary.unavailableStatus) }({translate text=$holdingsSummary.unavailableStatus}){/if}</a>
    </div>
  {/if}
  {if $holdingsSummary.isDownloadable}
    <div>Available Online from <a href='{$holdingsSummary.downloadLink}' {if !(isset($holdingsSummary.localDownload) || $holdingsSummary.localDownload == false )}target='_blank'{/if}>{$holdingsSummary.downloadText}</a></div>
  {else}
		<div class="holdableCopiesSummary">
		  {$holdingsSummary.numCopies} total {if $holdingsSummary.numCopies == 1}copy{else}copies{/if}, 
		  {$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} on shelf and 
		  {$holdingsSummary.holdableCopies} {if $holdingsSummary.holdableCopies == 1}is{else}are{/if} available by request.
		  {if $holdingsSummary.holdQueueLength >= 0}
		    <br/>{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.
		  {/if}
		  {if $holdingsSummary.numCopiesOnOrder > 0}
		    <br/>{$holdingsSummary.numCopiesOnOrder} copies are on order.
		  {/if}  
		</div>
  {/if}
 </div>