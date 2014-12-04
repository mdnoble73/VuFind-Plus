{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
{* Old link, links to Place Hold form rather than pop-up. Replaced with pop-up method below. plb 12-4-2014
		<a href="{$path}/Record/{$summId|escape:"url"}/Hold" class="btn btn-sm btn-block btn-primary" id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none" onclick="return VuFind.Account.followLinkIfLoggedIn(this);" title="Please login to place a hold">{translate text="Place Hold"}</a> *}
			<a href="#" class="btn btn-sm btn-block btn-primary" onclick="return VuFind.Record.showPlaceHold('{$summId}')" >{translate text="Place Hold"}</a>

		{/if}
	</div>
</div>
{/strip}