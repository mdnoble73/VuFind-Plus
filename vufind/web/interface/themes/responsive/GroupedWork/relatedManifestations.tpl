{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			<th>Format</th>
			<th>Availability</th>
			<th>Copies</th>
			<th></th>
		</tr>
		</thead>
		{foreach from=$relatedManifestations item=relatedManifestation}
			<tr>
				<td>{$relatedManifestation.format}</td>
				<td>{* TODO: Availability *}</td>
				<td>{$relatedManifestation.copies}</td>
				<td>
					{* TODO: Place Hold/Checkout *}
					<a href="{$relatedManifestation.holdUrl}" class="btn">Place Hold</a>
					<a href="{$relatedManifestation.url}" class="btn">More Info</a>
				</td>
			</tr>
		{/foreach}
	</table>
{/strip}