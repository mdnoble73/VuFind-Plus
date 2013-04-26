<div>
	<a href="#" id="userecontentreviewlink{$id}" class="userreviewlink resultAction" onclick="$('.userecontentreview').slideUp();$('#userecontentreview{$id}').slideDown();">
		<span class="silk comment_add">&nbsp;</span>Add a Review
	</a>
</div>
<div id="userecontentreview{$id}" class="userreview">
	<span class ="alignright unavailable closeReview" onclick="$('#userecontentreview{$id}').slideUp();" >Close</span>
	<div class='addReviewTitle'>Add your Review</div>
	{include file="EcontentRecord/submit-comments.tpl"}
</div>