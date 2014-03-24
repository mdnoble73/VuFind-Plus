{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Show hold/checkout button as appropriate *}
		{if $showHoldButton}
			{* Place hold link *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Hold" class="btn btn-sm btn-block" id="placeEcontentHold{$summId|escape:"url"}" style="display:none">{translate text="Place Hold"}</a>

			{* Checkout link *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Checkout" class="btn btn-sm btn-block" id="checkout{$summId|escape:"url"}" style="display:none">{translate text="Checkout"}</a>

			{* Access online link *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home?detail=holdingstab" class="btn btn-sm btn-block" id="accessOnline{$summId|escape:"url"}" style="display:none">{translate text="Access Online"}</a>
			{* Add to Wish List *}
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" class="btn btn-sm btn-block" id="addToWishList{$summId|escape:"url"}" style="display:none">{translate text="Add to Wishlist"}</a>
		{/if}
	</div>
</div>
{/strip}