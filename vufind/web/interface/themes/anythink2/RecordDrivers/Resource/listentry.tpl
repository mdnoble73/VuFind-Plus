<div id="record{$resource->record_id|regex_replace:"/\./":""|escape}" class="resultsList">
	<div class="imageColumn">
		 {if $user->disableCoverArt != 1}
		 <a href="{$url}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" id="descriptionTrigger{$resource->record_id|regex_replace:"/\./":""|escape:"url"}">
			<img src="{$path}/bookcover.php?id={$resource->record_id}&amp;isn={$resource->isbn|@formatISBN}&amp;size=small&amp;upc={$resource->upc}&amp;category={$resource->format_category|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
			<div id='descriptionPlaceholder{$resource->record_id|regex_replace:"/\./":""|escape}' style='display:none'></div>
		 {/if}

			<div class='requestThisLink' id="placeHold{$resource->record_id|escape:"url"}" style="display:none">
				<a class="button" href="{$url}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}/Hold">{translate text="Place Hold"}</a>
			</div>
	</div>

	<div id="searchStars{$resource->shortId|regex_replace:"/\./":""|escape}" class="resultActions">
		<div class="rate{$resource->record_id|regex_replace:"/\./":""|escape} stat">
			<div id="saveLink{$resource->record_id|regex_replace:"/\./":""|escape}">
				{if $allowEdit}
					<div class="actions-list">
						<label>List</label>
						<div>
							<a href="{$url}/MyResearch/Edit?id={$resource->record_id|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}&amp;source={$resource->source}" class="edit tool">{translate text='Edit'}</a>
							{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
							<a
							{if is_null($listSelected)}
								href="{$url}/MyResearch/Home?delete={$resource->record_id|escape:"url"}&src={$resource->source}"
							{else}
								href="{$url}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$resource->record_id|escape:"url"}&src={$resource->source}"
							{/if}
							class="delete tool" onclick="return confirm('Are you sure you want to delete this?');">{translate text='Delete'}</a>
						</div>
					</div>
				{/if}
			</div>
			<div class="actions-rate">
				<label>Rate</label>
				<div class="statVal">
					<span class="ui-rater">
						<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px"></span></span>
						(<span class="ui-rater-rateCount-{$resource->record_id|regex_replace:"/\./":""|escape} ui-rater-rateCount">0</span>)
					</span>
				</div>
			</div>
			{assign var=id value=$resource->record_id}
			{assign var=shortId value=$resource->shortId}
			<div class="actions-review">
				{include file="Record/title-review.tpl"}
			</div>
		</div>
		<script type="text/javascript">
			$(
				 function() {literal} { {/literal}
						 $('.rate{$resource->record_id|regex_replace:"/\./":""|escape}').rater({literal}{ {/literal}module: '{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}', recordId: '{$resource->record_id}',	rating:0.0, postHref: '{$url}/Record/{$resource->record_id|escape}/AJAX?method=RateTitle'{literal} } {/literal});
				 {literal} } {/literal}
			);
		</script>
	</div>

	<div class="resultDetails">
		<div class="resultItemLine1">
			<input class="result-selector" type="checkbox" name="selected[{$resource->record_id|escape:"url"}]" id="selected{$resource->record_id|regex_replace:"/\./":""|escape:"url"}" />
			<h2><a href="{$url}/{if $resource->source == 'VuFind'}Record{else}EcontentRecord{/if}/{$resource->record_id|escape:"url"}" class="title">{if !$resource->title}{translate text='Title not available'}{else}{$resource->title|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a></h2>
			{if $listTitleStatement}
			<div class="searchResultSectionInfo">
				{$listTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
			</div>
			{/if}
		</div>

		<div class="resultItemLine2">
			{if $resource->author}
				{translate text='by'}
				<a href="{$url}/Author/Home?author={$resource->author|escape:"url"}">{$resource->author|highlight:$lookfor}</a>
			{/if}

			{if $listDate}{translate text='Published'} {$listDate.0|escape}{/if}
		</div>

		{if is_array($listFormats)}
			{foreach from=$listFormats item=format}
				<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
			{/foreach}
		{else}
			<span class="iconlabel {$listFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$listFormats}</span>
		{/if}
		{if $resource->tags}
			{translate text='Your Tags'}:
			{foreach from=$resource->tags item=tag name=tagLoop}
				<a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a>{if !$smarty.foreach.tagLoop.last},{/if}
			{/foreach}
			<br />
		{/if}
		{if $resource->notes}
			{translate text='Notes'}:
			{foreach from=$resource->notes item=note}
				{$note|escape:"html"}<br />
			{/foreach}
		{/if}

		<div id="{if $resource->source=='VuFind'}holdingsSummary{else}holdingsEContentSummary{/if}{$resource->record_id|regex_replace:"/\./":""|escape:"url"}" class="holdingsSummary">
			<div class="statusSummary" id="statusSummary{$resource->record_id|regex_replace:"/\./":""|escape:"url"}">
				<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
			</div>
		</div>
	</div>


	<script type="text/javascript">
		addRatingId('{$resource->record_id|escape:"javascript"}');
		$(document).ready(function(){literal} { {/literal}
			addIdToStatusList('{$resource->record_id|escape:"javascript"}', '{$resource->source}');
			resultDescription('{$resource->record_id}','{$resource->record_id|regex_replace:"/\./":""}', '{$resource->source}');
		{literal} }); {/literal}
	</script>
</div>
