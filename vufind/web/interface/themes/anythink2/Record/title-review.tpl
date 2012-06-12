<p><a href="#" id="userreviewlink{$shortId}" class="write-review userreviewlink" onclick="$('.userreview').slideUp();$('#userreview{$shortId}').slideDown(); return false;">{translate text="Add a Comment"}</a></p>
<div id="userreview{$shortId}" class="userreview">
  <span class="alignright unavailable closeReview" onclick="$('#userreview{$shortId}').slideUp();">Close</span>
  <div class='addReviewTitle'>{translate text="Add a Comment"}</div>
  {include file="Record/submit-comments.tpl"}
</div>
