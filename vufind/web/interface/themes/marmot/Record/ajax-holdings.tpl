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
<SummaryDetails>
	<status>{$holdingsSummary.status|escape}</status>
	<callnumber>{$holdingsSummary.callnumber|escape}</callnumber>
	<showplacehold>{$holdingsSummary.showPlaceHold|escape}</showplacehold>
	<availablecopies>{$holdingsSummary.availableCopies|escape}</availablecopies>
	<holdablecopies>{$holdingsSummary.holdableCopies|escape}</holdablecopies>
	<numcopies>{$holdingsSummary.numCopies|escape}</numcopies>
	<class>{$holdingsSummary.class|escape}</class>
	<isDownloadable>{$holdingsSummary.isDownloadable|escape}</isDownloadable>
	<downloadLink>{$holdingsSummary.downloadLink|escape:'url'}</downloadLink>
	<downloadText>{$holdingsSummary.downloadText|escape}</downloadText>
	<showAvailabilityLine>{$holdingsSummary.showAvailabilityLine|escape}</showAvailabilityLine>
	<availableAt>{$holdingsSummary.availableAt|escape}</availableAt>
	<numAvailableOther>{$holdingsSummary.numAvailableOther|escape}</numAvailableOther>
</SummaryDetails>