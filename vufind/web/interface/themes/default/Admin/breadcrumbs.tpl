
<a href="{$path}/MyResearch/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{if $pageTemplate == 'view-alt.tpl'}
<em>{$pageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span class="divider">&raquo;</span>
