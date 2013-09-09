{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* Place hold link *}
		{if $showHoldButton}
			<a href="{$path}/Record/{$summId|escape:"url"}/Hold" class="btn btn-small btn-block" id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none" onclick="return VuFind.Account.followLinkIfLoggedIn(this);" title="Please login to place a hold">{translate text="Place Hold"}</a>
		{/if}
		{if $showMoreInfo !== false}
			<a href="{$recordUrl}" class="btn btn-small btn-block"><img src="/images/silk/information.png" alt="More Info">&nbsp;More Info</a>
		{/if}
		{*
		<div class="resultAction"><a href="#" class="cart" onclick="return addToBag('{$summId|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', '{$summShortId}');"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
		*}
		<a href="{$path}/Record/{$summId|escape:"url"}/SimilarTitles" class="btn btn-small btn-block"><img src="/images/silk/arrow_switch.png">&nbsp;More Like This</a>
		{if $showComments == 1}
			<a href="#" id="userreviewlink{$summShortId}" class="userreviewlink resultAction btn btn-small btn-block" title="Add a Review" onclick="return VuFind.Record.showReviewForm(this, '{$summId}', 'VuFind')">
				<img src="/images/silk/comment_add.png">&nbsp;Add a Review
			</a>
		{/if}
		{if $showFavorites == 1}
			<a href="{$path}/Resource/Save?id={$summId|escape:"url"}&amp;source=VuFind" onclick="return VuFind.Record.getSaveToListForm(this, '{$summId|escape}', 'VuFind');" class="btn btn-small btn-block"><img src="/images/silk/star_gold.png">&nbsp;{translate text='Add to favorites'}</a>
		{/if}
		{if $showTextThis == 1}
			<a href="{$path}/Record/{$id|escape:"url"}/SMS" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox", "#smsLink"); return false;' class="btn btn-small btn-block"><img src="/images/silk/phone.png">&nbsp;{translate text="Text this"}</a>
		{/if}
		{if $showEmailThis == 1}
			<a href="{$path}/Record/{$id|escape:"url"}/Email" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/Email?lightbox", "#mailLink"); return false;' class="btn btn-small btn-block"><img src="/images/silk/email.png">&nbsp;{translate text="Email this"}</a>
		{/if}
	</div>
</div>
{/strip}