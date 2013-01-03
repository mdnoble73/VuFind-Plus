<a href="{$path}/MyResearch/Home">{translate text='Your Account'}</a> <span>&gt;</span>
{if $reportData}
	<a href="{$reportData.parentLink}{if $filterString}?{$filterString}{/if}">{$reportData.parentName}</a> <span>&gt;</span>
	<em>{$reportData.name}</em>
	<span>&gt;</span>
{else if if $action != 'Dashboard'}
	<a href="{$path}/Report/Dashboard">{translate text='Dashboard'}</a> <span>&gt;</span>
	{if $pageTemplate == 'view-alt.tpl'}
		<em>{$pageTitle}</em>
	{else}
		<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
	{/if}
	<span>&gt;</span>
{/if}

