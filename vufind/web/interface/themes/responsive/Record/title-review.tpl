{strip}
<a href="#" id="userreviewlink{$shortId}" class="userreviewlink resultAction btn btn-small btn-block" onclick="return showReviewForm('{$shortId}', 'VuFind')">
	<img src="/images/silk/comment_add.png">&nbsp;Add a Review
</a>
<div id="userreview{$shortId}" class="userreview hidden">
  <span class ="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
	<div class='addReviewTitle'>Add your Review</div>
  
  {include file="Record/submit-comments.tpl"}
</div>
{/strip}