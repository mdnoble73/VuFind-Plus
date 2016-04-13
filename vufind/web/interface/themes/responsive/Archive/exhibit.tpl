{strip}
<div class="col-xs-12">
	{if $main_image}
		<div class="main-project-image">
			<img src="{$main_image}" class="img-responsive" usemap="#map">
		</div>
	{/if}

	<h2>
		{$title|escape}
	</h2>

	<div class="lead">
		{if $thumbnail}
			<img src="{$thumbnail}" class="img-responsive thumbnail exhibit-thumbnail">
		{/if}
		{$description}
	</div>


	<div class="related-exhibit-images {if count($relatedImages) >= 18}results-covers home-page-browse-thumbnails{else}browse-thumbnails-few{/if}">
		{foreach from=$relatedImages item=image}
			<figure class="browse-thumbnail">
				<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if}>
					<img src="{$image.image}" {if $image.title}alt="{$image.title}"{/if}>
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$image.title}</strong>
				</figcaption>
			</figure>
		{/foreach}
	</div>
</div>
{/strip}
