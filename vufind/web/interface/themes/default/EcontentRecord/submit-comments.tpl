<div id="commentForm{$id}">
	<div class="rateTitle">
		Rate this Title:
		{include file='EcontentRecord/title-rating.tpl' showNotInterested=false showReviewAfterRating=false}
	</div>
	Write Review
  <textarea name="econtentcomment" id="econtentcomment{$id}" rows="4" cols="40"></textarea>
  <span class="tool button" onclick='SaveEContentComment("{$id|escape}", {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal}); return false;'>{translate text="Save review"}</span>
</div>