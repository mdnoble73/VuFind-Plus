{strip}
<div id="commentForm{$shortId}">
	<div class="rateTitle">
		Rate this Title:
		{include file='Record/title-rating.tpl' showNotInterested=false showReviewAfterRating=false}
	</div>
	Write Review
  <textarea name="comment" id="comment{$shortId}" rows="4" cols="40"></textarea>
	<div style="margin-top:3px">
    <span class="tool button" onclick='SaveComment("{$id|escape}", "{$shortId}", {literal}{{/literal}
				  save_error: "{translate text='comment_error_save'}",
				  load_error: "{translate text='comment_error_load'}",
				  save_title: "{translate text='Save Review'}"{literal}}{/literal}); return false;'>{translate text="Submit Review"}</span>
	</div>
</div>
{/strip}