<div style ="font-size:12px;"><span id="userreviewlink{$shortId}" class="userreviewlink" onclick="$('.userreview').slideUp();$('#userreview{$shortId}').slideDown();"><span class="silk add">&nbsp;</span>Add a Review</span></div>
<div id="userreview{$shortId}" class="userreview">
  <span class ="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
  <div class='addReviewTitle'>Add your Review</div>
  
  {include file="Record/submit-comments.tpl"}
</div>