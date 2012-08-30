{if $user}
<a href="{$path}/MyResearch/Home">{translate text='Your Account'}</a> <span>&gt;</span>
{/if}
{if $shortPageTitle}
<em>{$shortPageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span>&gt;</span>
