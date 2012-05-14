<p><a href="#" id="userreviewlink{$shortId}" class="write-review userreviewlink" onclick="$('.userreview').slideUp();$('#userreview{$shortId}').slideDown(); return false;">Write review</a></p>
<div id="userreview{$shortId}" class="userreview">
  <span class="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();" >Close</span>
  <div class='addReviewTitle'>Add your Review</div>
  {include file="Record/submit-comments.tpl"}
</div>
