{strip}
<!--<h3>Description</h3>-->
<h3 class='description-element title'>{if $title}{$title|escape:html}{else}Description{/if}</h3>
<div class='description-element description'>{if $description}{$description|escape:html}{else}No description provided{/if}</div>

<!--
{if $length}
<div class='description-element length'>
	<span class='description-element-label'>Length: </span>{$length|escape:html}
</div>
{/if}
-->

{if $publisher}
<div class='description-element publisher'>
	<span class='description-element-label'>Publisher: </span>{$publisher|escape:html}
</div>
{/if}

{if $descriptionArray}
<div class='description-element descriptionArray'>
	<span class='description-element-label'>DescriptionArray: </span>{$descriptionArray|escape:html}
</div>
{/if}



{/strip}
