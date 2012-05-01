<div style ="font-size:12px;" class ="alignright"><span id="userreviewlink{$shortId}" class="add userreviewlink" onclick="$('.userreview').slideUp();$('#userreview{$shortId}').slideDown();">Add a Review</span></div>
<div id="userreview{$shortId}" class="userreview">
  <span class ="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
  <div class='addReviewTitle'>Add your Review</div>
  
  {include file="Record/submit-comments.tpl"}
</div>