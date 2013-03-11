{strip}
<div id="record{$summId|escape}" class="resultsList">
	<div class="selectTitle">
		<input type="checkbox" name="selected[econtentRecord{$summId|escape:"url"}]" class="titleSelect" id="selectedEcontentRecord{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('econtentRecord{$summId|escape:"url"}', '{$summTitle|replace:'"':''|replace:'&':'&amp;'|escape:'javascript'}', this);"{/if} />&nbsp;
	</div>
	
	<div class="imageColumn"> 
		<div id='descriptionPlaceholder{$summId|escape}' style='display:none' class='descriptionTooltip'></div>
		{if !isset($user->disableCoverArt) ||$user->disableCoverArt != 1}	
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$summId|escape:"url"}">
			<img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
		{/if}
		{if $showHoldButton}
			{if $eContentRecord->isOverDrive()}
				{* Place hold link *}
				<div class='requestThisLink' id="placeEcontentHold{$summId|escape:"url"}" style="display:none">
					<a href="#" class="button" onclick="return placeOverDriveHold('{$eContentRecord->externalId}')">{translate text="Place Hold"}</a>
				</div>
				
				{* Checkout link *}
				<div class='checkoutLink' id="checkout{$summId|escape:"url"}" style="display:none">
					<a href="#" class="button" onclick="return {if overDriveVersion==1}checkoutOverDriveItem{else}checkoutOverDriveItemOneClick{/if}('{$eContentRecord->externalId}')">{translate text="Checkout"}</a>
				</div>
			{else}
				
				{* Place hold link *}
				<div class='requestThisLink' id="placeEcontentHold{$summId|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Hold" class="button">{translate text="Place Hold"}</a>
				</div>
				
				{* Checkout link *}
				<div class='checkoutLink' id="checkout{$summId|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Checkout" class="button">{translate text="Checkout"}</a>
				</div>
			{/if}
		
			{* Access online link *}
			<div class='accessOnlineLink' id="accessOnline{$summId|escape:"url"}" style="display:none">
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home?detail=holdingstab" class="button">{translate text="Access Online"}</a>
			</div>
			{* Add to Wish List *}
			<div class='addToWishListLink' id="addToWishList{$summId|escape:"url"}" style="display:none">
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="button">{translate text="Add to Wishlist"}</a>
			</div>
		{/if}
	</div>

<div class="resultDetails">
	<div class="resultItemLine1">
	{if $summScore}({$summScore}) {/if}
	<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
	{if $summTitleStatement}
		<div class="searchResultSectionInfo">
			{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
		</div>
		{/if}
	</div>

	<div class="resultItemLine2">
		{if $summAuthor}
			{translate text='by'}&nbsp;
			{if is_array($summAuthor)}
				{foreach from=$summAuthor item=author}
					<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
				{/foreach}
			{else}
				<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
			{/if}
			&nbsp;
		{/if}
 
		{if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
	</div>
	
	<div class="resultItemLine3">
		{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
		{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
	</div>

	<div class="resultItemLine4">
	{if is_array($summFormats)}
		{strip}
		{foreach from=$summFormats item=format name=formatLoop}
			{if $smarty.foreach.formatLoop.index != 0}, {/if}
			<span class="icon {$format|lower|regex_replace:"/[^a-z0-9]/":""}"></span><span class="iconlabel">{translate text=$format}</span>
		{/foreach}
		{/strip}
	{else}
		<span class="icon {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}"></span><span class="iconlabel">{translate text=$summFormats}</span>
	{/if}
	</div>
	
	<div id = "holdingsEContentSummary{$summId|escape:"url"}" class="holdingsSummary">
		<div class="statusSummary" id="statusSummary{$summId|escape:"url"}">
			<span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
		</div>
	</div>
</div>

<div class="resultActions">
	{* Let the user rate this title *}
	{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
	{if $showComments == 1} 
		{assign var=id value=$summId scope="global"}
		{include file="EcontentRecord/title-review.tpl" id=$summId}
	{/if}
</div>


<script type="text/javascript">
	addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if});
	$(document).ready(function(){literal} { {/literal}
		resultDescription('{$summId}','{$summId}', 'eContent');
	{literal} }); {/literal}
</script>

</div>
{/strip}