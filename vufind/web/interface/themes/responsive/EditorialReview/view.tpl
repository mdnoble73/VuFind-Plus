<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
		
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content" class="col-md-9">
		<h1>Editorial Review: {$editorialReview->title}</h1>
		{if $user && ($user->hasRole('libraryAdmin') || $user->hasRole('opacAdmin') || $user->hasRole('contentEditor'))}
		<div id='actions'>
			<div class='button'><a href='{$path}/EditorialReview/{$editorialReview->editorialReviewId}/Edit'>Edit</a></div>
			{if $user->hasRole('opacAdmin')}
			<div class='button'><a href='{$path}/EditorialReview/{$editorialReview->editorialReviewId}/Delete' onclick="return confirm('Are you sure you want to delete this Editorial Review?');">Delete</a></div>
			{/if}
		</div>
		{/if}
		
		<div id='property'><span class='propertyLabel'>Title: </span><span class='propertyValue'>{$editorialReview->title}</span></div>
		<div id='property'><span class='propertyLabel'>Teaser: </span><span class='propertyValue'>{$editorialReview->teaser}</span></div>
		<div id='property'><span class='propertyLabel'>Review: </span><span class='propertyValue'>{$editorialReview->review}</span></div>
		<div id='property'><span class='propertyLabel'>Source: </span><span class='propertyValue'>{$editorialReview->source}</span></div>
		<div id='property'><span class='propertyLabel'>Record Id: </span><span class='propertyValue'>{$editorialReview->recordId}</span></div>
		<div id='property'><span class='propertyLabel'>Tab Name: </span><span class='propertyValue'>{$editorialReview->tabName}</span></div>
	</div>
</div>