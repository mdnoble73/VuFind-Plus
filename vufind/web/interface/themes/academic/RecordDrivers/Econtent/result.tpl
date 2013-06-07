{strip}
<div id="record{$summId|escape}">
	<div class="resultIndex">{$resultIndex}</div>
	<div class="selectTitle">
		<input type="checkbox" name="selected[econtentRecord{$summId|escape:"url"}]" class="titleSelect" id="selectedEcontentRecord{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('econtentRecord{$summId|escape:"url"}', '{$summTitle|regex_replace:"/(\/|:'\")$/":""|replace:'&':'&amp;'|escape:"javascript"}', this);"{/if} />&nbsp;
	</div>
	
	<div class="resultsList">
		<div id='descriptionPlaceholder{$summId|escape}' style='display:none' class='descriptionTooltip'></div>
		<div class="listResultImage">
			{if !isset($user->disableCoverArt) ||$user->disableCoverArt != 1}	
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$summId|escape:"url"}">
				<img src="{$bookCoverUrl}" alt="{translate text='Cover Image'}"/>
				</a>
			{/if}
			
			{* Let the user rate this title *}
			{include file="EcontentRecord/title-rating.tpl" ratingClass="" recordId=$summId shortId=$summShortId ratingData=$summRating}
			
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
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home?detail=formatstab" class="button">{translate text="Access Online"}</a>
			</div>
			{* Add to Wish List *}
			<div class='addToWishListLink' id="addToWishList{$summId|escape:"url"}" style="display:none">
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="button">{translate text="Add to Wishlist"}</a>
			</div>
			

		</div>
		
		<div class="resultitem">
			<div class="resultItemLine1">
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
						{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
			</div>
			
			{if $summAuthor}
				<div class="resultInformation" id="resultInformationAuthor{$summId|escape}"><span class="resultLabel">{translate text='Author'}:</span>
					<span class="resultValue">
						{if is_array($summAuthor)}
							{foreach from=$summAuthor item=author}
								<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
							{/foreach}
						{else}
							<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
						{/if}
					</span>
				</div>
			{/if}
			
			{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
			<div class="resultInformation" id="resultInformationPublisher{$summId|escape}"><span class="resultLabel">{translate text='Published'}:</span><span class="resultValue">{$summPublicationPlaces.0|escape} {$summPublishers.0|escape} {$summPublicationDates.0|escape}</span></div>
			{/if}
			
			{if $summFormats}
				<div class="resultInformation" id="resultInformationFormat{$summId|escape}"><span class="resultLabel">{translate text='Format'}:</span><span class="resultValue">
					{if is_array($summFormats)}
						{foreach from=$summFormats item=format name=formatLoop}
							{if $smarty.foreach.formatLoop.index != 0}, {/if}
							<span class="icon {$format|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$format}</span>
						{/foreach}
					{else}
						<span class="icon {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$summFormats}</span>
					{/if}
					</span>
				</div>
			{/if}
			{if $summPhysical.0}
			<div class="resultInformation" id="resultInformationPhysicalDesc{$summId|escape}"><span class="resultLabel">{translate text='Physical Desc'}:</span><span class="resultValue">{$summPhysical.0|escape}</span></div>
			{/if}
			<div class="resultInformation" id="resultInformationLocation{$summId|escape}"><span class="resultLabel">{translate text='Location'}:</span><span class="resultValue" id="locationValue{$summId|escape}">Online</span></div>
			<div class="resultInformation" id="resultInformationStatus{$summId|escape}"><span class="resultLabel">{translate text='Status'}:</span><span class="resultValue" id="statusValue{$summId|escape}">Loading...</span></div>
		</div>
	</div>
	
	<script type="text/javascript">
		addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if}, '{$useUnscopedHoldingsSummary}');
		$(document).ready(function(){literal} { {/literal}
			resultDescription('{$summId}','{$summId}', 'eContent');
		{literal} }); {/literal}
	</script>


	{* Clear floats so the record displays as a block*}
	<div class='clearer'></div>
</div>
{/strip}