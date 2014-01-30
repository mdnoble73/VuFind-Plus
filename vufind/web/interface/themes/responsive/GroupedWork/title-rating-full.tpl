<div class="full-rating">
	<div class="your-rating">
		<div class="rating-label">Your Rating</div>
		<div class="rater rate{$recordDriver->getPermanentId()|escape} stat"
		     data-show_review="{if $showReviewAfterRating === false}{$showReviewAfterRating}{else}true{/if}"
		     data-module="GroupedWork"
		     data-short_id="{$recordDriver->getPermanentId()}"
		     data-record_id="{$recordDriver->getPermanentId()}"
		     data-user_rating = "{$ratingData.user}"
				 >
			<div class="statVal">
				<span class="ui-rater">
					<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn{if $ratingData.user >0} userRated{/if}" style="width:0">&nbsp;</span></span>
				</span>
			</div>
		</div>
	</div>

	<div class="average-rating">
		<div class="rating-label">Average Rating</div>
		<div class="rater rate{$recordDriver->getPermanentId()|escape} stat"
	       data-show_review="{if $showReviewAfterRating === false}{$showReviewAfterRating}{else}true{/if}"
	       data-module="GroupedWork"
	       data-short_id="{$recordDriver->getPermanentId()}"
	       data-record_id="{$recordDriver->getPermanentId()}"
	       data-average_rating = "{$ratingData.average}"
	       >
			<div class="statVal">
				<span class="ui-rater">
					<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0">&nbsp;</span></span>
				</span>

				{* Show the number of reviews *}
				<span class="numberOfReviews">({$ratingData.count})</span>
			</div>
		</div>
	</div>

	<div class="rating-graph container-12">
		<div class="row">
			<div class="col-xs-3">5 star</div>
			<div class="col-xs-7"><div class="graph-bar" style="width:{$ratingData.barWidth5Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num5star})</div>
		</div>
		<div class="row">
			<div class="col-xs-3">4 star</div>
			<div class="col-xs-7"><div class="graph-bar" style="width:{$ratingData.barWidth4Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num4star})</div>
		</div>
		<div class="row">
			<div class="col-xs-3">3 star</div>
			<div class="col-xs-7"><div class="graph-bar" style="width:{$ratingData.barWidth3Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num3star})</div>
		</div>
		<div class="row">
			<div class="col-xs-3">2 star</div>
			<div class="col-xs-7"><div class="graph-bar" style="width:{$ratingData.barWidth2Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num2star})</div>
		</div>
		<div class="row">
			<div class="col-xs-3">1 star</div>
			<div class="col-xs-7"><div class="graph-bar" style="width:{$ratingData.barWidth1Star}%">&nbsp;</div></div>
			<div class="col-xs-2">({$ratingData.num1star})</div>
		</div>
	</div>
</div>