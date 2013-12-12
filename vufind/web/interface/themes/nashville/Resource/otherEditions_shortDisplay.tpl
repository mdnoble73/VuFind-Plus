{if is_array($otherEditions)}
	
    <div id="otherEditionsPopup_shortDisplay">
		{foreach from=$otherEditions item=resource name="recordLoop"}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if}">
				<div id="record{$resource->record_id|regex_replace:"/\./":""|escape}" class="resultsList">
					
                    
                    
                   
                    <div class="imageColumn">
						<a href="{$path}/{if strtoupper($resource->source) == 'VUFIND'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" class="title">{if is_array($resource->format)}
                        {foreach from=$resource->format item=format}<span>{translate text=$format}</span>
                        {/foreach}
						{else}
							<span>{translate text=$resource->format}</span>
						{/if}</a>
					</div>
			
					<div class="resultDetails_shortDisplay">
						
                        <div class="resultItemLine1">
						</div>

						<div class="resultItemLine2">
						</div>

						{* moved format up in code so it would be the link - Jenny 9/23/13 *}
                        {*{if is_array($resource->format)}
							{foreach from=$resource->format item=format}
								<span>{translate text=$format}</span>
							{/foreach}
						{else}
							<span>{translate text=$resource->format}</span>
						{/if}
                        *}

						{if $resource->source == 'eContent'}
						<div id = "holdingsEContentSummary{$resource->record_id|escape:"url"}" class="holdingsSummary">
							<div class="statusSummary" id="statusSummary{$resource->record_id|escape:"url"}">
								<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
							</div>
						</div>
						{else}
						<div id = "holdingsSummary{$resource->record_id|regex_replace:"/\./":""}" class="holdingsSummary">
							<div class="statusSummary" id="statusSummary{$resource->record_id|regex_replace:"/\./":""}">
								<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
							</div>
						</div>
						{/if}
					</div>

					{* removed the place hold button *}
                    
				</div>
			</div>
		{foreachelse}
			Sorry, we couldn't find any other copies of this title in different languages or formats.
		{/foreach}
	</div>
	<script type="text/javascript">
		{foreach from=$otherEditions item=resource}
		addIdToStatusList('{$resource->record_id|escape:"javascript"}', '{$resource->source}');
		resultDescription('{$resource->record_id}','{$resource->shortId}', '{$resource->source}');
		{/foreach}
		doGetStatusSummaries();
	</script>
{/if}
{if $enableMaterialsRequest}
	<p>
		Need this in another format? You can <a href="{$path}/MaterialsRequest/NewRequest">request it here</a>.
	</p>
{/if}
