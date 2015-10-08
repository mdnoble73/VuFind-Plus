{strip}
<div class="exploreMoreBar row">
	{foreach from=$exploreMoreOptions item=exploreMoreCategory key=categoryKey}
		<div class="explore-more-option">
			<figure class="thumbnail">
				<a href="{$exploreMoreCategory.link}">
					<img src="{$exploreMoreCategory.thumbnail}" alt="{$exploreMoreCategory.title}">
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$exploreMoreCategory.title}</strong>
				</figcaption>
			</figure>
		</div>
	{/foreach}
</div>
{/strip}