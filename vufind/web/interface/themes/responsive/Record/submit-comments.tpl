{strip}
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">Review</h3>
</div>
<div id="commentForm{$shortId}" class="modal-body">
	<form class="form-horizontal">
		<div class="rateTitle control-group">
			<label for="" class="control-label">Rate the Title</label>
			<div class="controls">
				{include file='Record/title-rating.tpl' showNotInterested=false showReviewAfterRating=false}
			</div>
		</div>
		<div class="control-group">
			<label for="comment{$shortId}" class="control-label">Write a Review</label>
			<div class="controls">
		    <textarea name="comment" id="comment{$shortId}" rows="4" class="input-xxlarge"></textarea>
			</div>
		</div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<span class="tool btn btn-primary" onclick='VuFind.Record.saveReview("{$id|escape}", "{$shortId}"); return false;'>{translate text="Submit Review"}</span>
</div>
{literal}
<script type="text/javascript">
	$(document).ready(function(){
		VuFind.Ratings.initializeRaters();
	});
</script>
{/literal}
{/strip}