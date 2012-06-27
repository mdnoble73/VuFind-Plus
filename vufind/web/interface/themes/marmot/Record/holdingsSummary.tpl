<div id = "holdingsSummary" class="holdingsSummary {$holdingsSummary.class}">
	{if $holdingsSummary.status == 'Available At'}
		<div class="{$holdingsSummary.class}" style= "font-size:13pt;">
			{if $holdingsSummary.numCopies == 0}
				No copies found
			{else}
				{* x of y copy(ies) is/are at location(s) and z other locations *}
				<a href='{$url}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>
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
			<a href='{$url}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text='Available now at'} {$holdingsSummary.numAvailableOther+$holdingsSummary.availableAt} Marmot {if $holdingsSummary.numAvailableOther == 1}Library{else}Libraries{/if}</a>
		</div>
	{else}
		<div class="{$holdingsSummary.class}" style= "font-size:11pt;">
			<a href='{$url}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if strlen($holdingsSummary.unavailableStatus) > 0 && $holdingsSummary.class == 'checkedOut'}({translate text=$holdingsSummary.unavailableStatus}){/if}</a>
		</div>
	{/if}
	{if $holdingsSummary.callnumber}
			<div class='callNumber'>
					<a href='{$url}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a>
			</div>
	{/if}
	{if false && $holdingsSummary.showPlaceHold}
			<div class='requestThisLink'>
					<a href="{$url}/Record/{$holdingsSummary.recordId|escape:"url"}/Hold" class="holdRequest" style="display:inline-block;font-size:11pt;">{translate text="Request This Title"}</a><br />
			</div>
	{/if}
	{if $holdingsSummary.isDownloadable}
			<div><a href='{$holdingsSummary.downloadLink}'	target='_blank'>{$holdingsSummary.downloadText}</a></div>
	{else}
		<div class="holdableCopiesSummary">
			{$holdingsSummary.numCopies} total {if $holdingsSummary.numCopies == 1}copy{else}copies{/if}, 
			{$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} on shelf and 
			{$holdingsSummary.holdableCopies} {if $holdingsSummary.holdableCopies == 1}is{else}are{/if} available by request.
			{if $holdingsSummary.holdQueueLength > 0}
				<br/>{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.
			{/if}
				{if $holdingsSummary.numCopiesOnOrder > 0}
					{$holdingsSummary.numCopiesOnOrder} copies are on order.
			{/if}	
		</div>
	{/if}
	{if $showOtherEditionsPopup}
		<div class="otherEditions">
			<a href="#" onclick="loadOtherEditionSummaries('{$holdingsSummary.recordId}', false)">Other Formats and Languages</a>
		</div>
	{/if}
 </div>