<div id="record{$listId|escape}" class="resultsList">
	<div class="selectTitle">
		<input type="checkbox" name="selected[{$listId|escape:"url"}]" id="selected{$listId|escape:"url"}" />&nbsp;
	</div>
					
	<div class="imageColumn"> 
		{if $user->disableCoverArt != 1}
			<a href="{$path}/Record/{$listId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$listId|escape:"url"}">
			<img src="{$path}/bookcover.php?id={$listId}&amp;issn={$listISSN}&amp;isn={$listISBN|@formatISBN}&amp;size=small&amp;upc={$listUPC}&amp;category={$listFormatCategory.0|escape:"url"}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
			<div id='descriptionPlaceholder{$listId|escape}' style='display:none'></div>
		{/if}
			
			{* Place hold link *}
			<div class='requestThisLink' id="placeHold{$listId|escape:"url"}" style="display:none">
				<a href="{$path}/Record/{$listId|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
			</div>
	</div>
	
	<div class="resultDetails">
		<div class="resultItemLine1">
		<a href="{$path}/Record/{$listId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$listTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$listTitle|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}</a>
		{if $listTitleStatement}
			<div class="searchResultSectionInfo">
				{$listTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
			</div>
			{/if}
		</div>
	
		<div class="resultItemLine2">
			{if $listAuthor}
				{translate text='by'}
				{if is_array($listAuthor)}
					{foreach from=$listAuthor item=author}
						<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
					{/foreach}
				{else}
					<a href="{$path}/Author/Home?author={$listAuthor|escape:"url"}">{$listAuthor|highlight:$lookfor}</a>
				{/if}
			{/if}
	 
			{if $listDate}{translate text='Published'} {$listDate.0|escape}{/if}
		</div>
	
		{if is_array($listFormats)}
			{foreach from=$listFormats item=format}
				<span class="icon {$format|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$format}</span>
			{/foreach}
		{else}
			<span class="icon {$listFormats|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$listFormats}</span>
		{/if}
		<div id = "holdingsSummary{$listId|escape:"url"}" class="holdingsSummary">
			<div class="statusSummary" id="statusSummary{$listId|escape:"url"}">
				<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
			</div>
		</div>
	</div>
	
	<div id ="searchStars{$listId|escape}" class="resultActions">
		<div class="rate{$listId|escape} stat">
			<div id="saveLink{$listId|escape}">
				{if $listEditAllowed}
						<a href="{$path}/MyResearch/Edit?id={$listId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}"><span class="silk edit">&nbsp;</span>{translate text='Edit'}</a>
						{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
						<a
						{if is_null($listSelected)}
							href="{$path}/MyResearch/Home?delete={$listId|escape:"url"}"
						{else}
							href="{$path}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$listId|escape:"url"}"
						{/if}
						onclick="return confirm('Are you sure you want to delete this?');"><span class="silk delete">&nbsp;</span>{translate text='Delete'}</a>
				{/if}
			</div>
			{* Let the user rate this title *}
			{include file="Record/title-rating.tpl" ratingClass="" recordId=$listId shortId=$listShortId ratingData=$ratingData showFavorites=0}
			
			{assign var=id value=$listId}
			{include file="Record/title-review.tpl"}
				
		</div>

			
	</div>
	<script type="text/javascript">
		addIdToStatusList('{$listId|escape:"javascript"}');
		$(document).ready(function(){literal} { {/literal}
				resultDescription('{$listId}','{$listId}','VuFind');
		{literal} }); {/literal}
		
	</script>

</div>

