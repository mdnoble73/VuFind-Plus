<div class="full-rating">
	{if $ratingData.user}
		<div class="your-rating row">
			<div class="rating-label col-sm-7">Your Rating</div>
			<div class="col-sm-5">
				<span class="ui-rater-starsOff" style="width:90px">
					<span class="ui-rater-starsOn userRated" style="width:{math equation="90*rating/5" rating=$ratingData.user}px"></span>
				</span>
			</div>
		</div>
	{/if}

	<div class="average-rating row">
		<div class="rating-label col-sm-6">Average Rating</div>
		<div class="col-sm-6">
			<span class="ui-rater-starsOff" style="width:90px">
					<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$ratingData.average}px"></span>
				</span>
		</div>
	</div>

	<div class="rating-graph">
		<div class="row">
			<div class="col-xs-4">5 star</div>
			<div class="col-xs-6"><div class="graph-bar" style="width:{$ratingData.barWidth5Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num5star})</div>
		</div>
		<div class="row">
			<div class="col-xs-4">4 star</div>
			<div class="col-xs-6"><div class="graph-bar" style="width:{$ratingData.barWidth4Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num4star})</div>
		</div>
		<div class="row">
			<div class="col-xs-4">3 star</div>
			<div class="col-xs-6"><div class="graph-bar" style="width:{$ratingData.barWidth3Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num3star})</div>
		</div>
		<div class="row">
			<div class="col-xs-4">2 star</div>
			<div class="col-xs-6"><div class="graph-bar" style="width:{$ratingData.barWidth2Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num2star})</div>
		</div>
		<div class="row">
			<div class="col-xs-4">1 star</div>
			<div class="col-xs-6"><div class="graph-bar" style="width:{$ratingData.barWidth1Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num1star})</div>
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 text-center">
		<span id="userreviewlink{$recordDriver->getPermanentId()}" class="userreviewlink btn btn-sm" title="Add a Review" onclick="return VuFind.GroupedWork.showReviewForm(this, '{$recordDriver->getPermanentId()}')">
			Add a Review
		</span>
		</div>
	</div>
</div>