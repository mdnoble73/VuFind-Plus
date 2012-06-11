
<a href="{$url}/MyAccount/Home">{translate text='Your Account'}</a> <span>&gt;</span>
{if $pageTemplate == 'view-alt.tpl' || isset($shortPageTitle)}
<em>{$shortPageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span>&gt;</span>
