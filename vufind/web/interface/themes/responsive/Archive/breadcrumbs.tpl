{strip}
{if $lastsearch}
	<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{if $lookfor}Archive Search "{$lookfor|truncate:30:"..."|escape}"{else}{translate text="Archive Search Results"}{/if}</a> <span class="divider">&raquo;</span>
{else}
	<a href="{$path}/Archive/Home">Local Digital Archive</a> <span class="divider">&raquo;</span>
{/if}
{if $breadcrumbText}
	{if $isFromExhibit && $parentExhibitUrl}
			<em><a href="{$parentExhibitUrl}" title="{$parentExhibitName}">{$parentExhibitName|truncate:30:"..."|escape}</a></em> <span class="divider">&raquo;</span>
	{/if}
	{if $isFromExhibit && $action != 'Exhibit' && $lastCollection}
		<em><a href="{$lastCollection}" title="{$collectionName}">{$collectionName|truncate:30:"..."|escape}</a></em> <span class="divider">&raquo;</span>
	{/if}
<em title="{$breadcrumbText}">{$breadcrumbText|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span>
{elseif $subTemplate!=""}
	<em>{$subTemplate|replace:'view-':''|replace:'.tpl':''|replace:'../MyResearch/':''|capitalize|translate}</em>
{/if}
{/strip}