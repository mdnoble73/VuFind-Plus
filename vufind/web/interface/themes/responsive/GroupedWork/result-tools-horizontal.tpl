{strip}
	<div class="result-tools-horizontal btn-toolbar" role="toolbar">
		{* Place hold link *}
		{if $showMoreInfo !== false}
			<div class="btn-group btn-group-sm">
				<a href="{$recordUrl}" class="btn btn-sm ">More Info</a>
			</div>
		{/if}
		{*
		<div class="resultAction"><a href="#" class="cart" onclick="return addToBag('{$summId|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', '{$summShortId}');"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
		*}
		{if $showComments == 1}
			<div class="btn-group btn-group-sm">
				<button id="userreviewlink{$summShortId}" class="userreviewlink resultAction btn btn-sm" title="Add a Review" onclick="return VuFind.GroupedWork.showReviewForm(this, '{$summId}')">
					Add a Review
				</button>
			</div>
		{/if}
		{if $showFavorites == 1}
			<div class="btn-group btn-group-sm">
				<button onclick="return VuFind.GroupedWork.getSaveToListForm(this, '{$summId|escape}');" class="btn btn-sm ">{translate text='Add to favorites'}</button>
			</div>
		{/if}
		{if $showTextThis == 1}
			<div class="btn-group btn-group-sm">
				<button onclick='return VuFind.ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox")' class="btn btn-sm ">{translate text="Text this"}</button>
			</div>
		{/if}
		{if $showEmailThis == 1}
			<div class="btn-group btn-group-sm">
				<button onclick="return VuFind.ajaxLightbox('{$path}/Record/{$id|escape}/Email?lightbox', true)" class="btn btn-sm ">
					{translate text="Email this"}
				</button>
			</div>
		{/if}
	</div>
{/strip}