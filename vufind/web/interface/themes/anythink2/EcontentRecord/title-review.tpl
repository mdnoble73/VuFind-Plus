<p><a href="#" id="userecontentreviewlink{$id}" class="write-review userreviewlink" onclick="$('.userecontentreview').slideUp();$('#userecontentreview{$id}').slideDown();">{translate text="Add a Comment"}</a></p>
<div id="userecontentreview{$id}" class="userreview">
  <span class="alignright unavailable closeReview" onclick="$('#userecontentreview{$id}').slideUp();" >Close</span>
  <div class='addReviewTitle'>{translate text="Add a Comment"}</div>
  {include file="EcontentRecord/submit-comments.tpl"}
</div>
