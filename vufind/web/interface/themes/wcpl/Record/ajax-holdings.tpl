<Holdings><![CDATA[
{include file="$module/view-holdings.tpl"}
]]></Holdings>
<HoldingsSummary><![CDATA[
{include file="$module/holdingsSummary.tpl"}
]]></HoldingsSummary>
<ShowPlaceHold>{$holdingsSummary.showPlaceHold}</ShowPlaceHold>
{if $holdingsSummary.eAudioLink}
<EAudioLink>{$holdingsSummary.eAudioLink|escape:'html'}</EAudioLink>
{/if}
{if $holdingsSummary.eBookLink}
<EBookLink>{$holdingsSummary.eBookLink|escape:'html'}</EBookLink>
{/if}