<div style ="font-size:12px;" class ="alignright"><span id="userecontentreviewlink{$id}" class="add" onclick="$('.userecontentreview').slideUp();$('#userecontentreview{$id}').slideDown();">Add a Review</span></div>
<div id="userecontentreview{$id}" class="userreview">
	<span class ="alignright unavailable closeReview" onclick="$('#userecontentreview{$id}').slideUp();" >Close</span>
	<div class='addReviewTitle'>Add your Review</div>
	{include file="EcontentRecord/submit-comments.tpl"}
</div>