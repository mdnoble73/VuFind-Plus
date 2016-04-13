{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<video width="100%" controls poster="{$medium_image}">
			<source src="{$videoLink}" type="video/mp4">
		</video>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}