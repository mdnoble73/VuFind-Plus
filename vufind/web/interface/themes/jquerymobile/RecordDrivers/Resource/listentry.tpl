<a href="{$path}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" rel="external">
	<div class="result">
		<h3>
			{if !$resource->title}{translate text='Title not available'}{else}{$resource->title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
		</h3>
	
		{if $resource->author}
			<p>{translate text='by'} {$resource->author|highlight:$lookfor}</p>
		{/if}
	 
		{if $listDate}<p><strong>{translate text='Published'}: </strong>{$listDate.0|escape}{/if}</p>
		
		{if $resource->format}
			<p><strong>Format:</strong>
			{if is_array($resource->format)}
				
				{foreach from=$resource->format item=format}
					{translate text=$format}
				{/foreach}
			{else}
				{translate text=$resource->format}
			{/if}
			</p>
		{/if}
		
		{if $resource->tags}
			<p><strong>{translate text='Your Tags'}:</strong>
			{foreach from=$resource->tags item=tag name=tagLoop}
				{$tag->tag|escape:"html"}{if !$smarty.foreach.tagLoop.last},{/if}
			{/foreach}
			</p>
		{/if}
		{if $resource->notes}
			<p><strong>
			{translate text='Notes'}: </strong>
			{foreach from=$resource->notes item=note}
				{$note|escape:"html"}<br />
			{/foreach}
			</p>
		{/if}
	</div>
</a>
