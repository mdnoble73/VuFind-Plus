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
					{if $relatedRecord.available}
						<div class="related_record_status available">Available</div>
						{$relatedRecord.availableCopies} of {$relatedRecord.copies} copies available.
					{else}
						<div class="related_record_status checked_out">Checked Out</div>
						{$relatedRecord.copies} {if $relatedRecord.copies > 1}copies{else}copy{/if} checked out.
					{/if}
					{if $relatedRecord.shelfLocation}
						<br/>{$relatedRecord.shelfLocation}
					{/if}
					{if $relatedRecord.shelfLocation}
						<br/>Shelf Location: {$relatedRecord.shelfLocation}
					{/if}
					{if $relatedRecord.callNumber}
						<br/>Call Number: {$relatedRecord.callNumber}
					{/if}

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