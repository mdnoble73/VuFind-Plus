{strip}
	<form class="form-horizontal" role="form">
		<div class="rateTitle form-group">
			<label for="rating" class="col-sm-3">Rate the Title</label>
			<div class="col-sm-9">
				<select name="rating" id="rating{$id}" class="form-control">
					<option value="-1">Select a rating</option>
					<option value="1">1 - Hated It</option>
					<option value="2">2 - Didn't Like It</option>
					<option value="3">3 - Liked It</option>
					<option value="4">4 - Really Liked It</option>
					<option value="5">5 - Loved It</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="comment{$id}" class="col-sm-3">Write a Review</label>
			<div class="col-sm-9">
				<textarea name="comment" id="comment{$id}" rows="4" cols="60" class="form-control"></textarea>
			</div>
		</div>
	</form>
{/strip}