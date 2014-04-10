{strip}
	<div class="browse-title thumbnail browse-thumbnail text-center">
		<div onclick="VuFind.GroupedWork.showGroupedWorkInfo('{$summId}')">
			<img class="hidden-xs hidden-sm visible-md" src="{$bookCoverUrlMedium}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
			<img class="visible-xs visible-sm hidden-md hidden-lg" src="{$bookCoverUrl}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
		</div>
		<div class="browse-rating" onclick="return VuFind.GroupedWork.showReviewForm(this, '{$summId}');">
			<span class="ui-rater-starsOff" style="width:90px">
				{if $ratingData.user}
					<span class="ui-rater-starsOn userRated" style="width:{math equation="90*rating/5" rating=$ratingData.user}px"></span>
				{else}
					<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$ratingData.average}px"></span>
				{/if}
			</span>
		</div>
	</div>
{/strip}