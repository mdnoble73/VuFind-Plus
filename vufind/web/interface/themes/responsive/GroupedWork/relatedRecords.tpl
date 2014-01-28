{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			{display_if_inconsistent array=$relatedRecords key="subtitle"}
				<th>Subtitle</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="edition"}
				<th>Edition</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="publisher"}
				<th>Publisher</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="publicationDate"}
				<th>Publication Date</th>
			{/display_if_inconsistent}
			{display_if_inconsistent array=$relatedRecords key="physical"}
				<th>Physical Desc.</th>
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
				{display_if_inconsistent array=$relatedRecords key="subtitle"}
					<td><a href="{$relatedRecord.url}">{$relatedRecord.subtitle}</a></td>
				{/display_if_inconsistent}
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
					<td><a href="{$relatedRecord.url}">{$relatedRecord.language}</a></td>
				{/display_if_inconsistent}
				<td>
					{if $relatedManifestation.available}
						{$relatedRecord.availableCopies} of {$relatedRecord.copies} copies available
					{else}
						{$relatedRecord.copies} {if $relatedRecord.copies > 1}copies{else}copy{/if} checked out
					{/if}
				</td>
				<td>
					<div class="btn-group">
					{foreach from=$relatedRecord.actions item=curAction}
						<a href="{$curAction.url}" class="btn btn-sm">{$curAction.title}</a>
					{/foreach}
					</div>
				</td>
			</tr>
		{/foreach}
	</table>
{/strip}