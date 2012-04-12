<div id = "holdingsSummary" class="holdingsSummary">
     {if $holdingsSummary.callnumber}
         <div class='callNumber'>
            Shelved at <a href='{$url}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a>
         </div>
     {/if}
     {if $holdingsSummary.status == "It's Here"}
        <div class="availability">
         Available at {$holdingsSummary.currentLocation}{if $holdingsSummary.numAvailableOther > 0} and <span class='availableAtList'>{$holdingsSummary.numAvailableOther} other {if $holdingsSummary.numAvailableOther==1}Library{else}Libraries{/if}</span>{/if} 
       </div>
     {elseif $holdingsSummary.status == 'Available At'}
       <div class="availability">
         Now Available at: <span class='availableAtList'>{$holdingsSummary.numAvailableOther} {if $holdingsSummary.numAvailableOther==1}Library{else}Libraries{/if}</span> 
       </div>
     {else}
       <div class="availability">
         <a href='{$url}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if strlen($holdingsSummary.unavailableStatus) > 0 && ($holdingsSummary.class == 'checkedOut') && ($holdingsSummary.statusfull != $holdingsSummary.unavailableStatus) }({translate text=$holdingsSummary.unavailableStatus}){/if}</a>
       </div>
     {/if}
     {if $holdingsSummary.isDownloadable}
         <div><a href='{$holdingsSummary.downloadLink}'  target='_blank'>{$holdingsSummary.downloadText}</a></div>
     {else}
		     <div class="holdableCopiesSummary">
		         {$holdingsSummary.numCopies} total {if $holdingsSummary.numCopies == 1}copy{else}copies{/if}, 
		         {$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} on shelf.
		         {if $holdingsSummary.holdQueueLength > 0}
		         	<br/>{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.
		         {/if}
		         {if $holdingsSummary.numCopiesOnOrder > 0}
			        <br/>{$holdingsSummary.numCopiesOnOrder} copies are on order.
			     {/if}  
		     </div>
     {/if}
     
 </div>