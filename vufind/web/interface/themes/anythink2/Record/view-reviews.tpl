{php}$index = 0;{/php}
{foreach from=$reviews item=providerList key=provider}
	{foreach from=$providerList item=review}
		{if $review.Content}
		<div class='review'>
			{if $review.Source}
				<div class='reviewSource'>{$review.Source}</div>
			{/if}
			<div id = 'review{php}$index ++;echo $index;{/php}'>
			{if $review.Teaser}
				 <div class="reviewTeaser" id="reviewTeaser{php}echo $index;{/php}">
				 {$review.Teaser} <span onclick="$('#reviewTeaser{php}echo $index;{/php}').hide();$('#reviewContent{php}echo $index;{/php}').show();" class='reviewMoreLink'>(more)</span>
				 </div>
				 <div class="reviewTeaser" id="reviewContent{php}echo $index;{/php}" style='display:none'>
				 {$review.Content}
				 <span onclick="$('#reviewTeaser{php}echo $index;{/php}').show();$('#reviewContent{php}echo $index;{/php}').hide();" class='reviewMoreLink'> (less)</span>
				 </div>
			{else}
				 <div class="reviewContent">{$review.Content}</div>
			{/if}
			<div class='reviewCopyright'>{$review.Copyright}</div>
			
			{if $provider == "amazon" || $provider == "amazoneditorial"}
				<div class='reviewProvider'><a target="new" href="http://amazon.com/dp/{$isbn}">{translate text="Supplied by Amazon"}</a></div>
			{elseif $provider == "syndetics"}
				<div class='reviewProvider'>{translate text="Powered by Syndetics"}</div>
			{/if}
		</div>
		{/if}
		</div>
		<hr/>
	{/foreach}
{/foreach}
