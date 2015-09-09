{strip}
	<h2>Explore More</h2>
	<div>
		{foreach from=$exploreMoreOptions item=exploreMoreCategory key=categoryKey}
			<div class="explore-more-option">
				{if $exploreMoreCategory.type == 'archive-collection'}
					<a href="{$exploreMoreCategory.link}"><img src="{$exploreMoreCategory.thumbnail}" alt="{$exploreMoreCategory.title}"/></a>
					<span class="explore-more-category-title">{$exploreMoreCategory.title}</span>
				{else}
					<span>Option {$categoryKey}</span>
				{/if}
			</div>
		{/foreach}
	</div>
{/strip}