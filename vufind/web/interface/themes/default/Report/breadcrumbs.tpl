<a href="{$path}/MyResearch/Home">{translate text='Your Account'}</a> <span>&gt;</span>
{if $action != 'Dashboard'}
	<a href="{$path}/Report/Dashboard">{translate text='Dashboard'}</a> <span>&gt;</span>
{/if}
{if $pageTemplate == 'view-alt.tpl'}
<em>{$pageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span>&gt;</span>
