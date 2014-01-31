<div class="userReviewList" id="userReviewList">
	{* Pull in comments from a separate file -- this separation allows the same template
		 to be used for refreshing this list via AJAX. *}
	{foreach from=$userReviews item=review}
		{include file="GroupedWork/view-user-review.tpl"}
	{foreachelse}
		<p>No borrower reviews currently exist.</p>
	{/foreach}
</div>
