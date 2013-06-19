{strip}
<div class='description-element description'>{if $description}{$description|escape:html}{else}No description provided{/if}</div>
{if $length}
<div class='description-element length'>
	<span class='description-element-label'>Length: </span>{$length|escape:html}
</div>
{/if}
<div class='description-element publisher'>
	<span class='description-element-label'>Publisher: </span>{$publisher|escape:html}
</div>
{/strip}