{strip}
	<div id="tableOfContentsPlaceholder" style="display:none"></div>

	{if $tableOfContents}
		<ul class='notesList'>
			{foreach from=$tableOfContents item=note}
				<li>{$note}</li>
			{/foreach}
		</ul>
	{/if}
{/strip}