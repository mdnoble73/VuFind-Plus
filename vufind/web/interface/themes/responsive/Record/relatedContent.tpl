{strip}
{foreach from=$enrichment.novelist.relatedContent item=contentSection}
	<dt>{$contentSection.title}</dt>
	<dd>
		<ul class="unstyled">
			{foreach from=$contentSection.content item=content}
				<li><a href="{$content.contentUrl}" onclick="return ajaxLightbox('{$path}/Resource/AJAX?method=GetNovelistData&novelistUrl={$content.contentUrl|escape:"url"}')">{$content.title}{if $content.author} by {$content.author}{/if}</a></li>
			{/foreach}
		</ul>
	</dd>
{/foreach}
{/strip}