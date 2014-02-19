{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
			<a href="{$path}/Record/{$summId|escape:"url"}/Hold" class="btn btn-sm btn-block" id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none" onclick="return VuFind.Account.followLinkIfLoggedIn(this);" title="Please login to place a hold">{translate text="Place Hold"}</a>
		{/if}
		<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()|escape:"url"}/Home" class="btn btn-sm btn-block">Other Editions &amp; Formats</a>
	</div>
</div>
{/strip}