{strip}
	<div class="col-xs-6 col-sm-4 col-md-3 col-lg-2 text-center browse-title">
		<div class="thumbnail browse-thumbnail">
			<div href="{$path}/GroupedWork/{$summId}/Home" onclick="VuFind.GroupedWork.showGroupedWorkInfo('{$summId}')">
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
	</div>
{* Insert separators at the appropriate locations *}
	{if $recordIndex % 6 == 0}
		<div class="clearfix visible-lg"></div>
	{/if}
	{if $recordIndex % 4 == 0}
		<div class="clearfix visible-md"></div>
	{/if}
	{if $recordIndex % 3 == 0}
		<div class="clearfix visible-sm"></div>
	{/if}
	{if $recordIndex % 2 == 0}
		<div class="clearfix visible-xs"></div>
	{/if}
{/strip}