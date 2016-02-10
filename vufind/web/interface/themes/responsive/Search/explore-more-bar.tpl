{strip}
	{* TODO: Consider renaming classes to assume they are under the exploreMoreBar class *}
<div class="exploreMoreBar row">
	<div class="label-left">
	{*<div class="label-top">*}
		<img src="{img filename='/interface/themes/responsive/images/ExploreMore.png'}" alt="{translate text='Explore More'}">
		<div class="exploreMoreBarLabel">{translate text='Explore More'}</div>
	</div>
	<div class="exploreMoreItemsContainer">
	{foreach from=$exploreMoreOptions item=exploreMoreCategory}
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
</div>
{/strip}