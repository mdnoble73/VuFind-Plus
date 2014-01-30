<div class='review' id="review_{$review->id}">
	<div class="reviewHeader">
		<div class="posted">
			{translate text='By'} {if strlen($review->displayName) > 0}{$review->displayName}{else}{$review->fullname}{/if}
			{if $review->dateRated != null && $review->dateRated > 0}
				On <span class='reviewDate'>{$review->dateRated|date_format}</span>
			{/if}
			{if $review->rating > 0}
				{* Display the rating the user gave it. *}
				<span class="ui-rater-starsOff" style="width:90px">
					<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$review->rating}px"></span>
				</span>
			{/if}
			{if $user && ($review->userid == $user->id || $user->hasRole('opacAdmin'))}
				<span onclick='deleteReview("{$id|escape:"url"}", "{$review->id}");' class="btn btn-sm"><i class="icon-minus-sign"></i> {translate text='Delete'}</span>
			{/if}
		</div>
	</div>
	{$review->review|escape:"html"}
</div>