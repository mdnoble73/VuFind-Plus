
<a href="{$path}/MyResearch/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{if $pageTemplate == 'view-alt.tpl' || isset($shortPageTitle)}
<em>{$shortPageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span class="divider">&raquo;</span>
