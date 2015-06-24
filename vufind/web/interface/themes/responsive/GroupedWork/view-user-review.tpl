{strip}
<div class='review' id="review_{$review->id}">
	<div class="reviewHeader">
		{*<div class="posted">*}
			<h5>{translate text='By'} {if strlen($review->displayName) > 0}{$review->displayName} {else}{$review->fullname} {/if}
			{if $review->dateRated != null && $review->dateRated > 0}
				On <span class='reviewDate'>{$review->dateRated|date_format}</span>
			{/if}
			{if $showRatings && $review->rating > 0}
				{* Display the rating the user gave it. *}
				<span class="ui-rater-starsOff" style="width:90px">
					<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$review->rating}px"></span>
				</span>
			{/if}
			{if $user && ($review->userid == $user->id || $user->hasRole('opacAdmin'))}
				&nbsp;<span onclick='return VuFind.GroupedWork.deleteReview("{$id|escape:"url"}", "{$review->id}");' class="btn btn-danger btn-xs">&times; {translate text='Delete'}</span>
			{/if}</h5>
		{*</div>*}
	</div>

	<blockquote style="white-space: pre-line">{$review->review|escape:"html"}</blockquote>
</div>
{/strip}