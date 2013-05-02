{strip}
{if $showRatings == 1}
	<div class="{$ratingClass}">
		<div class="rateEContent{$shortId|escape} stat" data-show_review="{if $showReviewAfterRating === false}{$showReviewAfterRating}{else}true{/if}">
			<div class="statVal">
				<span class="ui-rater">
					<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn{if $ratingData.user >0} userRated{/if}" style="width:0px">&nbsp;</span></span><br/>
				</span>
			</div>
			{if $showNotInterested !== false}
				<span class="button notInterested" onclick="return markNotInterested('eContent', '{$recordId}');">Not&nbsp;Interested</span>
			{/if}
		</div>
		<script type="text/javascript">
			$(
				function() {literal} { {/literal}
					$('.rateEContent{$shortId|escape}{$starPostFixId}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: '{$shortId}', rating:'{if $ratingData.user >0}{$ratingData.user}{else}{$ratingData.average}{/if}', postHref: '{$path}/EcontentRecord/{$recordId|escape}/AJAX?method=RateTitle', {literal} } {/literal});
				{literal} } {/literal}
			);
		</script>
	</div>
{/if}
{/strip}