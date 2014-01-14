{foreach from=$recordDriver->getUserReviews() item=review}
  <div class='review'>
  	<div class="reviewHeader">

	    <div class="posted">
		    {translate text='By'} {if strlen($review->displayName) > 0}{$review->displayName}{else}{$review->fullname}{/if}
		    {if $review->dateRated != null && $review->dateRated > 0}
		    On <span class='reviewDate'>{$review->dateRated|date_format}
			  {/if}
			    {if $user && ($review->userid == $user->id || $user->hasRole('opacAdmin'))}
				    <span onclick='deleteReview("{$id|escape:"url"}", "{$review->id}"");' class="btn btn-sm"><i class="icon-minus-sign"></i> {translate text='Delete'}</span>
			    {/if}
		    </span>
	    </div>
    </div>
    {$review->review|escape:"html"}
  </div>
{/foreach}