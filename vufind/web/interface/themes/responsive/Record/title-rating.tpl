{if $showRatings == 1}
	{strip}
	<div {if $ratingClass}class="{$ratingClass}"{/if}>
		{/strip}
		<div class="rater rate{$shortId|escape} stat"
		     data-show_review="{if $showReviewAfterRating === false}false{else}{$showComments}{/if}"
		     data-module="Record"
		     data-short_id="{$shortId}"
		     data-record_id="{$recordId}"
		     data-average_rating = "{$ratingData.average}"
		     data-user_rating = "{$ratingData.user}"
						>
			{strip}
			<div class="statVal">
				<span class="ui-rater">
					<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn{if $ratingData.user >0} userRated{/if}" style="width:0">&nbsp;</span></span><br/>
				</span>
			</div>
		</div>
		{if $showNotInterested == true}
			<span class="button notInterested" title="Select Not Interested if you don't want to see this title again." onclick="return markNotInterested('VuFind', '{$recordId}');">Not&nbsp;Interested</span>
		{/if}
	</div>
	{/strip}
{/if}