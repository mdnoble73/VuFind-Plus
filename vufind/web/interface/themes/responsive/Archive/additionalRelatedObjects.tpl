{strip}
	{foreach from=$directlyRelatedObjects.objects item=image}
		<figure class="browse-thumbnail">
			<a href="{$image.link}" {if $image.label}data-title="{$image.label|urlencode}"{/if}>
				<img src="{$image.image}" {if $image.label}alt="{$image.label|urlencode}"{/if}>
			</a>
			<figcaption class="explore-more-category-title">
				<strong>{$image.label}</strong>
			</figcaption>
		</figure>
	{/foreach}
{/strip}