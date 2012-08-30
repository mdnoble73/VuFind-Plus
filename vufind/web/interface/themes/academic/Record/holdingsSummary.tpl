<div id = "holdingsSummary" class="holdingsSummary {$holdingsSummary.class}">
     {if $holdingsSummary.status == 'Available At'}
       <div class="{$holdingsSummary.class}" style= "font-size:13pt;">
         Status:
         {if $holdingsSummary.numCopies == 0}
         No copies found
         {else}
           {* x of y copy(ies) is/are at location(s) and z other locations *}
           <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>
           {if (strlen($holdingsSummary.availableAt) > 0)}
              Available now{if $holdingsSummary.inLibraryUseOnly} for in library use{/if} at <br /><span class='availableAtList'>{$holdingsSummary.availableAt}{if ($holdingsSummary.numAvailableOther) > 0},<br />and {$holdingsSummary.numAvailableOther} other location{if ($holdingsSummary.numAvailableOther) > 1}s{/if}.{/if}</span>
           {else}
              Available now{if $holdingsSummary.inLibraryUseOnly} for in library use{/if}.
           {/if}
           </a>
         {/if}
         
       </div>
     {elseif ($holdingsSummary.status) == 'Marmot'}
       <div class="{$holdingsSummary.class}" style= "font-size:11pt;">
         <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text='Available now at'} {$holdingsSummary.numAvailableOther+$holdingsSummary.availableAt} Marmot {if $holdingsSummary.numAvailableOther == 1}Library{else}Libraries{/if}</a>
       </div>
     {else}
       <div class="{$holdingsSummary.class}" style= "font-size:11pt;">
         <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if strlen($holdingsSummary.unavailableStatus) > 0 && $holdingsSummary.class == 'checkedOut'}({translate text=$holdingsSummary.unavailableStatus}){/if}</a>
       </div>
     {/if}
     {if $holdingsSummary.callnumber}
         <div class='callNumber'>
         Call Number: <a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a>
         </div>
     {/if}
     {if false && $holdingsSummary.showPlaceHold}
         <div class='requestThisLink'>
            <a href="{$path}/Record/{$holdingsSummary.recordId|escape:"url"}/Hold" class="holdRequest" style="display:inline-block;font-size:11pt;">{translate text="Request this"}</a><br />
         </div>
     {/if}
     {if $holdingsSummary.isDownloadable}
         <div><a href='{$holdingsSummary.downloadLink}'  target='_blank'>{$holdingsSummary.downloadText}</a></div>
     {/if}
 </div>