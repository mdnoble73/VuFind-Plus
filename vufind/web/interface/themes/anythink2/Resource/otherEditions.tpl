<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
	<a onclick="hideLightbox(); return false;" href="">close</a>
	{translate text='Other Editions'}
</div>
<div id="popupboxContent" class="content">
	{if is_array($otherEditions)}
		<div id="otherEditionsPopup">
			{foreach from=$otherEditions item=resource name="recordLoop"}
				<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if}">
					<div id="record{$resource->record_id|regex_replace:"/\./":""|escape}" class="resultsList">
						<div class="imageColumn"> 
							 {if $user->disableCoverArt != 1}
							 <a href="{$path}/{if strtoupper($resource->source) == 'VUFIND'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" id="descriptionTrigger{$resource->record_id|regex_replace:"/\./":""|escape:"url"}">
								<img src="{$path}/bookcover.php?id={$resource->record_id}&amp;isn={$resource->isbn|@formatISBN}&amp;size=small&amp;upc={$resource->upc}&amp;category={$resource->format_category|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
								</a>
								<div id='descriptionPlaceholder{$resource->record_id|regex_replace:"/\./":""|escape}' style='display:none'></div>
							 {/if}
								
								{* Place hold link *}
								<div class='requestThisLink' id="placeHold{$resource->record_id|escape:"url"}" style="display:none">
									<a href="{$path}/{if strtoupper($resource->source) == 'VUFIND'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}/Hold"><img src="{img filename="place_hold.png"}" alt="Place Hold"/></a>
								</div>
						</div>
					
						<div class="resultDetails">
							<div class="resultItemLine1">
							<a href="{$path}/{if strtoupper($resource->source) == 'VUFIND'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" class="title">{if !$resource->title}{translate text='Title not available'}{else}{$resource->title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
							</div>
						
							<div class="resultItemLine2">
								{if $resource->author}
									{translate text='by'}
									<a href="{$path}/Author/Home?author={$resource->author|escape:"url"}">{$resource->author|highlight:$lookfor}</a>
								{/if}
							</div>
						
							{if is_array($resource->format)}
								{foreach from=$resource->format item=format}
									<span>{translate text=$format}</span>
								{/foreach}
							{else}
								<span>{translate text=$resource->format}</span>
							{/if}
							
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
					</div>
				</div>
			{foreachelse}
				Sorry, we couldn't find any other copies of this title in different languages or formats.  
			{/foreach}
		</div>
	{/if}
</div>
<script type="text/javascript">
{foreach from=$otherEditions item=resource}
	addIdToStatusList('{$resource->record_id|escape:"javascript"}', '{$resource->source}');
	resultDescription('{$resource->record_id}','{$resource->shortId}', '{$resource->source}');
{/foreach}
	doGetStatusSummaries();
</script>