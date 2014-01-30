<div class="userReviewList" id="userReviewList">
	{if count($userReviews)}
		<h3>Customer Reviews</h3>
	{/if}
	{* Pull in comments from a separate file -- this separation allows the same template
		 to be used for refreshing this list via AJAX. *}
	{foreach from=$userReviews item=review}
		{include file="GroupedWork/view-user-review.tpl"}
	{/foreach}
</div>
