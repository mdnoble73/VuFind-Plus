<a href="{$path}/MyResearch/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{if $reportData}
	<a href="{$reportData.parentLink}{if $filterString}?{$filterString}{/if}">{$reportData.parentName}</a> <span class="divider">&raquo;</span>
	<em>{$reportData.name}</em>
	<span class="divider">&raquo;</span>
{else if if $action != 'Dashboard'}
	<a href="{$path}/Report/Dashboard">{translate text='Dashboard'}</a> <span class="divider">&raquo;</span>
	{if $pageTemplate == 'view-alt.tpl'}
		<em>{$pageTitle}</em>
	{else}
		<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
	{/if}
	<span class="divider">&raquo;</span>
{/if}

