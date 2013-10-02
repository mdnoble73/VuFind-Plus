{if is_array($otherEditions)}
	<table class="table-striped table-condensed">
		<tr>
			<th>Format</th>
			<th>Language</th>
			<th>Availability</th>
			<th>&nbsp;</th>
		</tr>
		{foreach from=$otherEditions item=resource name="recordLoop"}
			<tr>
				<td>
					<a href="{$path}/{if strtoupper($resource->source) == 'VUFIND'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" class="title">
						{*
						{if !$resource->title}{translate text='Title not available'}{else}{$resource->title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
						*}
						{if is_array($resource->format)}
							{foreach from=$resource->format item=format}
								<span>{translate text=$format}</span>
							{/foreach}
						{else}
							<span>{translate text=$resource->format}</span>
						{/if}

					</a>
				</td>

				<td>
					{implode subject=$resource->language glue=","}
				</td>

				<td>
					{if $resource->source == 'eContent'}
						<div class="statusValue" id="statusValue{$resource->record_id|escape:"url"}">
							<small>{translate text='Loading'}...</small>
						</div>
					{else}
						<div class="statusValue" id="statusValue{$resource->record_id|regex_replace:"/\./":""}">
							<small>{translate text='Loading'}...</small>
						</div>
					{/if}
				</td>

				<td>
					{* Place hold link *}
					<div class='requestThisLink resultAction btn btn-small' id="placeHold{$resource->record_id|escape:"url"}" style="display:none">
						<a href="{$path}/{if strtoupper($resource->source) == 'VUFIND'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}/Hold">{translate text="Place Hold"}</a>
					</div>
				</td>
			</tr>
		{foreachelse}
			Sorry, we couldn't find any other copies of this title in different languages or formats.
		{/foreach}
	</table>
	<script type="text/javascript">
		{foreach from=$otherEditions item=resource}
		VuFind.ResultsList.addIdToStatusList('{$resource->record_id|escape:"javascript"}', '{$resource->source}', false);
		{/foreach}
	</script>
{/if}
{if $enableMaterialsRequest}
	<p>
		Need this in another format? You can <a href="{$path}/MaterialsRequest/NewRequest">request it here</a>.
	</p>
{/if}