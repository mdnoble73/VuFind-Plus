{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			{* <th>Id</th> *}
			<th>Format</th>
			<th>Edition</th>
			<th>Language</th>
			<th>Availability</th>
			<th>Copies</th>
			<th></th>
		</tr>
		</thead>
		{foreach from=$relatedRecords item=relatedRecord}
			<tr>
				{* <td>{$relatedRecord.id}</td> *}
				<td>{$relatedRecord.format}</td>
				<td>{$relatedRecord.edition}</td>
				<td>{$relatedRecord.language}</td>
				<td>{if $relatedManifestation.available}Available{else}Checked Out{/if}</td>
				<td>{$relatedRecord.copies}</td>
				<td>
					<div class="btn-group">
					{foreach from=$relatedRecord.actions item=curAction}
						<a href="{$curAction.url}" class="btn btn-small">{$curAction.title}</a>
					{/foreach}
					</div>
				</td>
			</tr>
		{/foreach}
	</table>
{/strip}