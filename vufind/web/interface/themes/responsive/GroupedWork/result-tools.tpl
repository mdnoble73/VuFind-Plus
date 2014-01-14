{strip}
	<div class="btn-group-vertical">
		{* Place hold link *}
		{if $showMoreInfo !== false}
			<button href="{$recordUrl}" class="btn btn-sm "><img src="/images/silk/information.png" alt="More Info">&nbsp;More Info</button>
		{/if}
		{*
		<div class="resultAction"><a href="#" class="cart" onclick="return addToBag('{$summId|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', '{$summShortId}');"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
		*}
		<button href="{$path}/GroupedWork/{$summId|escape:"url"}/SimilarTitles" class="btn btn-sm "><img src="/images/silk/arrow_switch.png">&nbsp;More Like This</button>
		{if $showComments == 1}
			<button href="#" id="userreviewlink{$summShortId}" class="userreviewlink resultAction btn btn-sm " title="Add a Review" onclick="return VuFind.Record.showReviewForm(this, '{$summId}', 'VuFind')">
				<img src="/images/silk/comment_add.png">&nbsp;Add a Review
			</button>
		{/if}
		{if $showFavorites == 1}
			<button href="{$path}/GroupedWork/Save?id={$summId|escape:"url"}&amp;source=VuFind" onclick="return VuFind.Record.getSaveToListForm(this, '{$summId|escape}', 'VuFind');" class="btn btn-sm "><img src="/images/silk/star_gold.png">&nbsp;{translate text='Add to favorites'}</button>
		{/if}
		{if $showTextThis == 1}
			<button href="{$path}/GroupedWork/{$id|escape:"url"}/SMS" onclick='return VuFind.ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox")' class="btn btn-sm "><img src="/images/silk/phone.png">&nbsp;{translate text="Text this"}</button>
		{/if}
		{if $showEmailThis == 1}
			<button href="{$path}/GroupedWork/{$id|escape:'url'}/Email?lightbox" onclick="return VuFind.ajaxLightbox('{$path}/Record/{$id|escape}/Email?lightbox', true)" class="btn btn-sm ">
				<img src="/images/silk/email.png" />&nbsp;{translate text="Email this"}
			</button>
		{/if}
	</div>
{/strip}