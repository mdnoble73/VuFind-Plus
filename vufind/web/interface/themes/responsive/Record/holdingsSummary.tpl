<div id = "holdingsSummary{$holdingsSummary.shortId}" class="holdingsSummary {$holdingsSummary.class}">
	{if $offline}
		<div class="holdingsSummaryStatusLine">
			{$holdingsSummary.status}
		</div>
	{elseif $holdingsSummary.status == 'Available At'}
		<div class="holdingsSummaryStatusLine {$holdingsSummary.class}">
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
		<div class="holdingsSummaryStatusLine {$holdingsSummary.class}">
			<a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text='Available now at'} {$holdingsSummary.numAvailableOther+$holdingsSummary.availableAt} Marmot {if $holdingsSummary.numAvailableOther == 1}Library{else}Libraries{/if}</a>
		</div>
	{elseif ($holdingsSummary.class) == 'here'}
		<div class="holdingsSummaryStatusLine {$holdingsSummary.class}">
			<a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if $holdingsSummary.location}<br/>{$holdingsSummary.location}{/if}</a>
		</div>
	{else}
		<div class="holdingsSummaryStatusLine {$holdingsSummary.class}">
			<a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{translate text=$holdingsSummary.status} {if false && strlen($holdingsSummary.unavailableStatus) > 0 && $holdingsSummary.class == 'checkedOut'}({translate text=$holdingsSummary.unavailableStatus}){/if}</a>
		</div>
	{/if}
	{if $holdingsSummary.callnumber}
		<div class='callNumber'>
				<a href='{$path}/Record/{$holdingsSummary.recordId|escape:"url"}#holdings'>{$holdingsSummary.callnumber}</a>
		</div>
	{/if}
	{if false && $holdingsSummary.showPlaceHold}
		<div class='requestThisLink'>
			<a href="{$path}/Record/{$holdingsSummary.recordId|escape:"url"}/Hold" class="holdRequest button" style="display:inline-block;">{translate text="Request This Title"}</a><br />
		</div>
	{/if}
	{if $holdingsSummary.isDownloadable}
		<div><a href='{$holdingsSummary.downloadLink}'	target='_blank'>{$holdingsSummary.downloadText}</a></div>
	{else}
		{if !$offline && $showCopiesLineInHoldingsSummary}
			<div class="holdableCopiesSummary">
				{$holdingsSummary.numCopies} total {if $holdingsSummary.numCopies == 1}copy{else}copies{/if},
				{$holdingsSummary.availableCopies} {if $holdingsSummary.availableCopies == 1}is{else}are{/if} on shelf. 
				{* 
				and {$holdingsSummary.holdableCopies} {if $holdingsSummary.holdableCopies == 1}is{else}are{/if} available by request.
				*}
			</div>
			{if $holdingsSummary.holdQueueLength > 0 || $holdingsSummary.numCopiesOnOrder > 0}
				<div class="holdableCopiesSummary2">
					{if $holdingsSummary.holdQueueLength > 0}
						{$holdingsSummary.holdQueueLength} {if $holdingsSummary.holdQueueLength == 1}person is{else}people are{/if} on the wait list.
					{/if}
					{if $holdingsSummary.numCopiesOnOrder > 0}
						{$holdingsSummary.numCopiesOnOrder} {if $holdingsSummary.numCopiesOnOrder == 1}copy is{else}copies are{/if} on order.
					{/if}
				</div>
			{/if}
		{/if}
	{/if}
 </div>