<div>
	<a href="#" id="userecontentreviewlink{$id}" class="userreviewlink resultAction" onclick="return showReviewForm('{$id}', 'econtent')">
		<img src="/images/silk/comment_add.png">&nbsp;Add a Review
	</a>
</div>
<div id="userecontentreview{$id}" class="userreview hidden">
	<span class ="alignright unavailable closeReview" onclick="$('#userecontentreview{$id}').slideUp();" >Close</span>
	<div class='addReviewTitle'>Add your Review</div>
	{include file="EcontentRecord/submit-comments.tpl"}
</div>