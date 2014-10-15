{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			{if $relatedManifestation.format == 'eBook' || $relatedManifestation.format == 'eAudiobook'}
				<th>Source</th>
			{/if}
			{display_if_inconsistent array=$relatedRecords key="edition"}
				<th>Edition</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="publisher"}
				<th>Publisher</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="publicationDate"}
				<th>Pub. Date</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="physical"}
				<th>Phys Desc.</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="language"}
				<th>Language</th>
			{/display_if_inconsistent}
			<th>Availability</th>
			<th></th>
		</tr>
		</thead>
		{foreach from=$relatedRecords item=relatedRecord}
			<tr>
				{* <td>
				{$relatedRecord.holdRatio}
				</td> *}
				{if $relatedManifestation.format == 'eBook' || $relatedManifestation.format == 'eAudiobook'}
					<td><a href="{$relatedRecord.url}">{$relatedRecord.source}</a></td>
				{/if}
				{display_if_inconsistent array=$relatedRecords key="edition"}
					<td><a href="{$relatedRecord.url}">{$relatedRecord.edition}</a></td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="publisher"}
					<td><a href="{$relatedRecord.url}">{$relatedRecord.publisher}</a></td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="publicationDate"}
					<td><a href="{$relatedRecord.url}">{$relatedRecord.publicationDate}</a></td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="physical"}
					<td><a href="{$relatedRecord.url}">{$relatedRecord.physical}</a></td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="language"}
					<td><a href="{$relatedRecord.url}">{implode subject=$relatedRecord.language glue=","}</a></td>
				{/display_if_inconsistent}
				<td>
					{if $relatedRecord.availableHere && $showItsHere}
						{if $relatedRecord.availableOnline}
							<div class="related-manifestation-shelf-status available">Available Online</div>
						{elseif $relatedRecord.allLibraryUseOnly}
							<div class="related-manifestation-shelf-status available">It's Here (library use only)</div>
						{else}
							<div class="related-manifestation-shelf-status available">It's Here</div>
						{/if}
					{elseif $relatedRecord.availableLocally}
						{if $relatedRecord.availableOnline}
							<div class="related-manifestation-shelf-status available">Available Online</div>
						{elseif $relatedRecord.allLibraryUseOnly}
							<div class="related-manifestation-shelf-status available">On Shelf (library use only)</div>
						{elseif $onInternalIP}
							<div class="related-manifestation-shelf-status availableOther">Available at another branch</div>
						{else}
							<div class="related-manifestation-shelf-status available">On Shelf</div>
						{/if}
					{elseif $relatedRecord.availableOnline}
						<div class="related-manifestation-shelf-status available">Available Online</div>
					{elseif $relatedRecord.inLibraryUseOnly}
						<div class="related-manifestation-shelf-status available">In Library Use Only</div>
					{elseif $relatedRecord.available && $relatedRecord.hasLocalItem}
						<div class="related-manifestation-shelf-status availableOther">Checked Out/Available Elsewhere</div>
					{elseif $relatedRecord.available}
						<div class="related-manifestation-shelf-status availableOther">Available from another library</div>
					{else}
						<div class="related-manifestation-shelf-status checked_out">{$relatedRecord.groupedStatus}</div>
					{/if}

					{if $relatedRecord.numHolds > 0 || $relatedRecord.onOrderCopies > 0}
						<div class="smallText">
							{if $relatedRecord.numHolds > 0}
								{$relatedRecord.numHolds} {if $relatedRecord.numHolds == 1}person is{else}people are{/if} on the wait list
								{if $relatedRecord.onOrderCopies > 0}, {else}.{/if}
							{/if}
							{if $relatedRecord.onOrderCopies > 0}
								{$relatedRecord.onOrderCopies} {if $relatedRecord.onOrderCopies == 1}copy{else}copies{/if} on order.
							{/if}
						</div>
					{/if}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedRecord.itemSummary totalCopies=$relatedRecord.copies itemSummaryId=$relatedRecord.id}

					{if $relatedRecord.usageRestrictions}
						<br/>{$relatedRecord.usageRestrictions}
					{/if}
				</td>
				<td>
					<div class="btn-group btn-group-vertical btn-group-sm">
						<a href="{$relatedRecord.url}" class="btn btn-sm btn-info">More Info</a>
						{foreach from=$relatedRecord.actions item=curAction}
							<a href="{$curAction.url}" {if $curAction.onclick}onclick="{$curAction.onclick}"{/if} class="btn btn-sm btn-default">{$curAction.title}</a>
						{/foreach}
					</div>
				</td>
			</tr>
		{/foreach}
	</table>
{/strip}