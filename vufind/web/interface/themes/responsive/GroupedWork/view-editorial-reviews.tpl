{strip}
	{if count($editorialReviews)}
		<h3>Editorial Reviews</h3>
	{/if}
	{foreach from=$editorialReviews item=editorialReview}
		<div class='review'>
		{if $editorialReview->title}
			<h4 class='reviewSource'>{$editorialReview->title}</h4>
		{/if}
		<div>
			<p class="reviewContent">{$editorialReview->review}</p>
			<div class='reviewCopyright'><small>{$editorialReview->source}</small></div>
		</div>
	{/foreach}
{/strip}