{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Show hold/checkout button as appropriate *}
		{if $showHoldButton}
			{if $eContentRecord->isOverDrive()}
				{* Place hold link *}
				<a href="#" class="btn btn-small btn-block" id="placeHold{$summId|escape:"url"}" style="display:none" onclick="return VuFind.OverDrive.placeOverDriveHold('{$eContentRecord->externalId}')">{translate text="Place Hold"}</a>

				{* Checkout link *}
				<a href="#" class="btn btn-small btn-block" id="checkout{$summId|escape:"url"}" style="display:none" onclick="return {if overDriveVersion==1}VuFind.OverDrive.checkoutOverDriveItem{else}VuFind.OverDrive.checkoutOverDriveItemOneClick{/if}('{$eContentRecord->externalId}')">{translate text="Checkout"}</a>
			{else}
				{* Place hold link *}
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Hold" class="btn btn-small btn-block" id="placeEcontentHold{$summId|escape:"url"}" style="display:none">{translate text="Place Hold"}</a>

				{* Checkout link *}
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Checkout" class="btn btn-small btn-block" id="checkout{$summId|escape:"url"}" style="display:none">{translate text="Checkout"}</a>

				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="btn btn-small btn-block" id="addToWishList{$summId|escape:"url"}" style="display:none">{translate text="Add To Wish List"}</a>
			{/if}

			{* Access online link *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home?detail=holdingstab" class="btn btn-small btn-block" id="accessOnline{$summId|escape:"url"}" style="display:none">{translate text="Access Online"}</a>
			{* Add to Wish List *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="btn btn-small btn-block" id="addToWishList{$summId|escape:"url"}" style="display:none">{translate text="Add to Wishlist"}</a>
		{/if}
		{if $showMoreInfo !== false && $recordUrl}
			<a href="{$recordUrl}" class="btn btn-small btn-block"><img src="/images/silk/information.png">&nbsp;More Info</a>
		{/if}
		{*
		<div class="resultAction"><a href="#" class="cart" onclick="return addToBag('{$id|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', 'EcontentRecord{$summId|escape:"url"}');"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
		*}
		{if $summId != -1}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/SimilarTitles" class="btn btn-small btn-block"><img src="/images/silk/arrow_switch.png">&nbsp;</span>More Like This</a>
			{if $showComments == 1}
				{assign var=id value=$summId scope="global"}
				{include file="EcontentRecord/title-review.tpl" id=$summId}
			{/if}
			{if $showFavorites == 1}
				<a id="saveLink{$id|escape}" class="btn btn-small btn-block" href="{$path}/Resource/Save?id={$summId|escape:"url"}&amp;source=eContent" onclick="getSaveToListForm('{$summId|escape}', 'eContent');return false;"><img src="/images/silk/star_gold.png">&nbsp;{translate text='Add to favorites'}</a>
			{/if}
			{if $showTextThis == 1}
				<a href="{$path}/EcontentRecord/{$id|escape:"url"}/SMS" id="smsLink" class="btn btn-small btn-block" onclick="VuFind.ajaxLightbox('{$path}/EcontentRecord/{$id|escape}/SMS?lightbox', true); return false;"><img src="/images/silk/phone.png">&nbsp;{translate text="Text this"}</a>
			{/if}
			{if $showEmailThis == 1}
				<a href="{$path}/EcontentRecord/{$id|escape:"url"}/Email" id="mailLink" class="btn btn-small btn-block" onclick="VuFind.ajaxLightbox('{$path}/EcontentRecord/{$id|escape}/Email?lightbox', true); return false;"><img src="/images/silk/email.png" />&nbsp;{translate text="Email this"}</a>
			{/if}
		{/if}
	</div>
</div>
{/strip}