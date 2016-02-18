{strip}
	<div class="related-exhibit-images results-covers home-page-browse-thumbnails">
		{foreach from=$relatedEntities item=image}
			<figure class="thumbnail">
				<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if}>
					<img src="{$image.thumbnail}" {if $image.title}alt="{$image.title}"{/if}/>
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$image.title}</strong>
				</figcaption>
			</figure>
		{/foreach}
	</div>
{/strip}