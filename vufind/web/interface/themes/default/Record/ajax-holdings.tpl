<Holdings><![CDATA[
{include file="$module/view-holdings.tpl"}
]]></Holdings>
<HoldingsSummary><![CDATA[
{include file="$module/holdingsSummary.tpl"}
]]></HoldingsSummary>
<ShowPlaceHold>{$holdingsSummary.showPlaceHold}</ShowPlaceHold>
<ShowCheckout>{$holdingsSummary.showCheckout}</ShowCheckout>
{if isset($holdingsSummary.showAccessOnline)}
<ShowAccessOnline>{$holdingsSummary.showAccessOnline}</ShowAccessOnline>
{/if}
{if isset($holdingsSummary.showAddToWishlist)}
<ShowAddToWishlist>{$holdingsSummary.showAddToWishlist}</ShowAddToWishlist>
{/if}