<div class='tableOfContents'>
{foreach from=$tocData item=chapter}
<div class='tocEntry'>
<span class='tocLabel'>{$chapter.label}</span>
<span class='tocTitle'>{$chapter.title}</span>
<span class='tocPage'>{$chapter.page}</span>
</div>
{/foreach}
</div>