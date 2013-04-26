{strip}
	{if $showRatings == 1 || $showFavorites == 1}
		<div class="{$ratingClass}">
			<div class="rate{$shortId|escape} stat">
				{if $showFavorites == 1}
					<div id="saveLink{$recordId|escape}" class="button">
						<a href="{$path}/Resource/Save?id={$recordId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListForm('{$recordId|escape}', 'VuFind'); return false;">{translate text='Add to favorites'}</a>
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
									$('.rate{$shortId|escape}{$starPostFixId}').rater({literal}{ {/literal}module: 'Record', recordId: '{$shortId}', rating:'{if $ratingData.user >0}{$ratingData.user}{else}{$ratingData.average}{/if}', postHref: '{$path}/Record/{$recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
									{literal} } {/literal}
				);
			</script>
		</div>
	{/if}
{/strip}