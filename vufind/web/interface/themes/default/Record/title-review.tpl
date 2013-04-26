{strip}
<div>
	<a href="#" id="userreviewlink{$shortId}" class="userreviewlink resultAction" onclick="$('.userreview').slideUp();$('#userreview{$shortId}').slideDown();">
		<span class="silk comment_add">&nbsp;</span>Add a Review
	</a>
</div>
<div id="userreview{$shortId}" class="userreview">
  <span class ="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
  <div class='addReviewTitle'>Add your Review</div>
  
  {include file="Record/submit-comments.tpl"}
</div>
{/strip}