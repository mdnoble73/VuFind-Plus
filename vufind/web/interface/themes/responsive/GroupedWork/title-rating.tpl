{if $showRatings == 1}
	<div {if $ratingClass}class="{$ratingClass} rate{$summId}"{/if}>
		<div class="title-rating" onclick="return VuFind.GroupedWork.showReviewForm(this, '{$summId}');">
			<span class="ui-rater-starsOff" style="width:90px">
				{if $ratingData.user}
					<span class="ui-rater-starsOn userRated" style="width:{math equation="90*rating/5" rating=$ratingData.user}px"></span>
				{else}
					<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$ratingData.average}px"></span>
				{/if}
			</span>
		</div>
		{if $showNotInterested == true}
			<button class="button notInterested" title="Select Not Interested if you don't want to see this title again." onclick="return VuFind.GroupedWork.markNotInterested('{$summId}');">Not&nbsp;Interested</button>
		{/if}
	</div>
{/if}