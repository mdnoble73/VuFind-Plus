{strip}
	<h2>Explore More</h2>
	<div>
		{foreach from=$exploreMoreOptions item=exploreMoreCategory key=categoryKey}
			<span>Option {$categoryKey}</span>
		{/foreach}
	</div>
{/strip}