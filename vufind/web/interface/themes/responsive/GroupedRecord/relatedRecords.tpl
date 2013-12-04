{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			<th>Id</th>
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
				<td>{$relatedRecord.id}</td>
				<td>{$relatedRecord.format}</td>
				<td>{$relatedRecord.edition}</td>
				<td>{$relatedRecord.language}</td>
				<td>{* TODO: Availability *}</td>
				<td>{$relatedRecord.copies}</td>
				<td>
					{* TODO: Place Hold/Checkout *}
					<a href="{$relatedRecord.holdUrl}" class="btn">Place Hold</a>
					<a href="{$relatedRecord.url}" class="btn">More Info</a>
				</td>
			</tr>
		{/foreach}
	</table>
{/strip}