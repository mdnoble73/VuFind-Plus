{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			<th>Id</th>
			{* <th>Format</th> *}
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
			<th>Copies</th>
			<th></th>
		</tr>
		</thead>
		{foreach from=$relatedRecords item=relatedRecord}
			<tr>
				<td><a href="{$relatedRecord.url}">{$relatedRecord.id}</a></td>
				{* <td>{$relatedRecord.format}</td> *}
				{display_if_inconsistent array=$relatedRecords key="subtitle"}
					<td>{$relatedRecord.subtitle}</td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="edition"}
					<td>{$relatedRecord.edition}</td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="publisher"}
					<td>{$relatedRecord.publisher}</td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="publicationDate"}
					<td>{$relatedRecord.publicationDate}</td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="physical"}
					<td>{$relatedRecord.physical}</td>
				{/display_if_inconsistent}
				{display_if_inconsistent array=$relatedRecords key="language"}
					<td>{$relatedRecord.language}</td>
				{/display_if_inconsistent}
				<td>{if $relatedManifestation.available}Available{else}Checked Out{/if}</td>
				<td>{$relatedRecord.copies}</td>
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