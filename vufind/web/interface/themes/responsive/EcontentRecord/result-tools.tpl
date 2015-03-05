{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Show hold/checkout button as appropriate *}
		{if $showHoldButton}
			{if $eContentRecord->isOverDrive()}
				{* Place hold link *}
				<a href="#" class="btn btn-sm btn-block" id="placeHold{$summId|escape:"url"}" style="display:none" onclick="return VuFind.OverDrive.placeOverDriveHold('{$eContentRecord->externalId}')">{translate text="Place Hold"}</a>

				{* Checkout link *}
				<a href="#" class="btn btn-sm btn-block" id="checkout{$summId|escape:"url"}" style="display:none" onclick="return {if overDriveVersion==1}VuFind.OverDrive.checkoutOverDriveItem{else}VuFind.OverDrive.checkoutOverDriveItemOneClick{/if}('{$eContentRecord->externalId}')">{translate text="Checkout"}</a>
			{else}
				{* Place hold link *}
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Hold" class="btn btn-sm btn-block" id="placeEcontentHold{$summId|escape:"url"}" style="display:none">{translate text="Place Hold"}</a>

				{* Checkout link *}
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Checkout" class="btn btn-sm btn-block" id="checkout{$summId|escape:"url"}" style="display:none">{translate text="Checkout"}</a>

				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="btn btn-sm btn-block" id="addToWishList{$summId|escape:"url"}" style="display:none">{translate text="Add To Wish List"}</a>
			{/if}

			{* Access online link *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home?detail=holdingstab" class="btn btn-sm btn-block" id="accessOnline{$summId|escape:"url"}" style="display:none">{translate text="Access Online"}</a>
			{* Add to Wish List *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="btn btn-sm btn-block" id="addToWishList{$summId|escape:"url"}" style="display:none">{translate text="Add to Wishlist"}</a>
		{/if}
	</div>
</div>
{/strip}