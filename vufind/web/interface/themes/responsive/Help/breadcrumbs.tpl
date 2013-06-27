<li>
	{if $pageTemplate == 'view-alt.tpl' || isset($shortPageTitle)}
	<em>{$shortPageTitle}</em>
	{else}
	<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
	{/if}
	<span class="divider">&raquo;</span>
</li>