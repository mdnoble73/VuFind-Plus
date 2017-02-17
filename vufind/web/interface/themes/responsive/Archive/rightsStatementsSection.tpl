{strip}
	{foreach from=$rightsStatements item=rightsStatement}
		<div class="rightsStatement">{$rightsStatement}</div>
	{/foreach}
	{if $rightsHolderTitle}
		<div><em>Rights held by <a href="{$rightsHolderLink}">{$rightsHolderTitle}</a></em></div>
	{/if}
	{if $rightsCreatorTitle}
		<div><em>Rights created by <a href="{$rightsCreatorLink}">{$rightsCreatorTitle}</a></em></div>
	{/if}
{/strip}