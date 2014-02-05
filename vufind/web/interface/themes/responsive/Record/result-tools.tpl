{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
			<a href="{$path}/Record/{$summId|escape:"url"}/Hold" class="btn btn-sm btn-block" id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none" onclick="return VuFind.Account.followLinkIfLoggedIn(this);" title="Please login to place a hold">{translate text="Place Hold"}</a>
		{/if}
		<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()|escape:"url"}/Home" class="btn btn-sm btn-block">Other Editions &amp; Formats</a>
		{if $showComments == 1}
			<a href="#" id="userreviewlink{$summShortId}" class="userreviewlink resultAction btn btn-sm btn-block" title="Add a Review" onclick="return VuFind.Record.showReviewForm(this, '{$summId}', 'VuFind')">
				Add a Review
			</a>
		{/if}
		{if $showFavorites == 1}
			<a href="{$path}/Resource/Save?id={$summId|escape:"url"}&amp;source=VuFind" onclick="return VuFind.Record.getSaveToListForm(this, '{$summId|escape}', 'VuFind');" class="btn btn-sm btn-block">{translate text='Add to favorites'}</a>
		{/if}
		{if $showTextThis == 1}
			<a href="{$path}/Record/{$id|escape:"url"}/SMS" onclick='return VuFind.ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox")' class="btn btn-sm btn-block">{translate text="Text this"}</a>
		{/if}
		{if $showEmailThis == 1}
			<a href="{$path}/Record/{$id|escape:'url'}/Email?lightbox" onclick="return VuFind.ajaxLightbox('{$path}/Record/{$id|escape}/Email?lightbox', true)" class="btn btn-sm btn-block">
				{translate text="Email this"}
			</a>
		{/if}
	</div>
</div>
{/strip}