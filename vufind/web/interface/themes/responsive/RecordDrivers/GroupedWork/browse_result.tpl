{strip}
	<div class="col-xs-6 col-sm-4 col-md-3 col-lg-2 text-center browse-title">
		<div class="thumbnail">
			<a href="{$path}/GroupedWork/{$summId}/Home">
				<img class="hidden-xs hidden-sm visible-md" src="{$bookCoverUrlMedium}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				<img class="visible-xs visible-sm hidden-md hidden-lg" src="{$bookCoverUrl}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
			</a>
			{include file="GroupedWork/title-rating.tpl" id=$summId showNotInterested=false showReviewAfterRating=false}
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