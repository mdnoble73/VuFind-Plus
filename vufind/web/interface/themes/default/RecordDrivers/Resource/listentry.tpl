<div id="record{$resource->record_id|regex_replace:"/\./":""|escape}" class="resultsList">
	<div class="selectTitle">
		<input type="checkbox" name="selected[{$resource->record_id|escape:"url"}]" id="selected{$resource->record_id|regex_replace:"/\./":""|escape:"url"}" />&nbsp;
	</div>
				
	<div class="imageColumn"> 
		 {if $user->disableCoverArt != 1}
		 <a href="{$path}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" id="descriptionTrigger{$resource->record_id|regex_replace:"/\./":""|escape:"url"}">
			<img src="{$path}/bookcover.php?id={$resource->record_id}&amp;issn={$resource->issn}&amp;isn={$resource->isbn|@formatISBN}&amp;size=small&amp;upc={$resource->upc}&amp;category={$resource->format_category|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
			<div id='descriptionPlaceholder{$resource->record_id|regex_replace:"/\./":""|escape}' style='display:none'></div>
		 {/if}
			
			{* Place hold link *}
			<div class='requestThisLink' id="placeHold{$resource->record_id|escape:"url"}" style="display:none">
				<a href="{$path}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}/Hold"><img src="{img filename="place_hold.png"}" alt="Place Hold"/></a>
			</div>
	</div>

	<div class="resultDetails">
		<div class="resultItemLine1">
		<a href="{$path}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" class="title">{if !$resource->title}{translate text='Title not available'}{else}{$resource->title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
		{if $listTitleStatement}
			<div class="searchResultSectionInfo">
				{$listTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
			</div>
			{/if}
		</div>
	
		<div class="resultItemLine2">
			{if $resource->author}
				{translate text='by'}
				<a href="{$path}/Author/Home?author={$resource->author|escape:"url"}">{$resource->author|highlight:$lookfor}</a>
			{/if}
	 
			{if $listDate}{translate text='Published'} {$listDate.0|escape}{/if}
		</div>
	
		{if is_array($resource->format)}
			{foreach from=$resource->format item=format}
				<span class="icon {$format|lower|regex_replace:"/[^a-z0-9]/":""}"></span><span class="iconlabel">{translate text=$format}</span>
			{/foreach}
		{elseif strlen($resource->format) > 0}
			<span class="icon {$resource->format|lower|regex_replace:"/[^a-z0-9]/":""}"></span><span class="iconlabel">{translate text=$resource->format}</span>
		{/if}
		<br/>
		{if $resource->tags}
			{translate text='Your Tags'}:
			{foreach from=$resource->tags item=tag name=tagLoop}
				<a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a>{if !$smarty.foreach.tagLoop.last},{/if}
			{/foreach}
			<br />
		{/if}
		{if $resource->notes}
			{translate text='Notes'}: 
			{foreach from=$resource->notes item=note}
				{$note|escape:"html"}<br />
			{/foreach}
		{/if}
		
		<div id = "{if $resource->source=='VuFind'}holdingsSummary{else}holdingsEContentSummary{/if}{$resource->record_id|regex_replace:"/\./":""|escape:"url"}" class="holdingsSummary">
			<div class="statusSummary" id="statusSummary{$resource->record_id|regex_replace:"/\./":""|escape:"url"}">
				<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
			</div>
		</div>
	</div>

	<div class="resultActions">
		{if $allowEdit}
				<a href="{$path}/MyResearch/Edit?id={$resource->record_id|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}&amp;source={$resource->source}"><span class="silk edit">&nbsp;</span>{translate text='Edit'}</a>
				{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
				<a
				{if is_null($listSelected)}
					href="{$path}/MyResearch/Home?delete={$resource->record_id|escape:"url"}&amp;src={$resource->source}"
				{else}
					href="{$path}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$resource->record_id|escape:"url"}&amp;src={$resource->source}"
				{/if}
				onclick="return confirm('Are you sure you want to delete this?');"><span class="silk delete">&nbsp;</span>{translate text='Delete'}</a>
		{/if}
			
		{* Let the user rate this title *}
		{if $resource->source == 'VuFind'}
			{include file="Record/title-rating.tpl" ratingClass="" recordId=$resource->record_id shortId=$resource->shortId ratingData=$resource->getRatingData() showFavorites=0}
		{else}
			{* Let the user rate this title *}
			{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$resource->record_id shortId=$resource->record_id ratingData=$resource->getRatingData() showFavorites=0}
		{/if}
		
		{assign var=id value=$resource->record_id}
		{assign var=shortId value=$resource->shortId}
		{include file="Record/title-review.tpl"}
			
	</div>
	<script type="text/javascript">
		$(document).ready(function(){literal} { {/literal}
			addIdToStatusList('{$resource->record_id|escape:"javascript"}', '{$resource->source}');
			resultDescription('{$resource->record_id}','{$resource->record_id|regex_replace:"/\./":""}', '{$resource->source}');
		{literal} }); {/literal}
	</script>
</div>

