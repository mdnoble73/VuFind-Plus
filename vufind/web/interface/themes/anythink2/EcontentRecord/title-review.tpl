<p><a href="#" id="userecontentreviewlink{$id}" class="write-review userreviewlink" onclick="$('.userecontentreview').slideUp();$('#userecontentreview{$id}').slideDown();">Add a Review</a></p>
<div id="userecontentreview{$id}" class="userreview">
  <span class="alignright unavailable closeReview" onclick="$('#userecontentreview{$id}').slideUp();" >Close</span>
  <div class='addReviewTitle'>Add your Review</div>
  {include file="EcontentRecord/submit-comments.tpl"}
</div>
