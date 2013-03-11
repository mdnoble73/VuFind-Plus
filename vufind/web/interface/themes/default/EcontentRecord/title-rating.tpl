{strip}
{if $showRatings == 1 || $showFavorites == 1}
	<div class="{$ratingClass}">
		<div id="rateEContent{$shortId|escape}{$starPostFixId}" class="rateEContent{$shortId|escape} stat">
			{if $showFavorites == 1} 
			<div id="saveLink{$recordId|escape}">
				<a href="{$path}/EcontentRecord/{$recordId|escape:"url"}/Save" style="padding-left:8px;" onclick="getLightbox('Record', 'Save', '{$recordId|escape}', '', '{translate text='Add to favorites'}', 'EcontentRecord', 'Save', '{$recordId|escape}'); return false;">{translate text='Add to favorites'}</a>
				{if $user}
				<script type="text/javascript">
					getSaveStatuses('{$recordId|escape:"javascript"}');
				</script>
				{/if}
			</div>
			{/if}
			{if $showRatings == 1}
			<div class="statVal">
				<span class="ui-rater">
					<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn{if $ratingData.user >0} userRated{/if}" style="width:0px">&nbsp;</span></span><br/>
				</span>
			</div>
			{/if}
		</div>
		<script type="text/javascript">
			$(
				function() {literal} { {/literal}
					$('#rateEContent{$shortId|escape}{$starPostFixId}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: '{$shortId}', rating:{if $ratingData.user >0}{$ratingData.user}{else}{$ratingData.average}{/if}, postHref: '{$path}/EcontentRecord/{$recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
				{literal} } {/literal}
			);
		</script>
	</div>
{/if}
{/strip}