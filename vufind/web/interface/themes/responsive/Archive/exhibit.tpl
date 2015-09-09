{strip}
<div class="col-xs-12">
	<div class="main-project-image">
		<img src="{$repositoryUrl}/{$main_image}" class="img-responsive"/>
	</div>

	<h2>
		{$title|escape}
	</h2>
	{$description}

	{* TODO: Figure out why the heck lightbox doesn't work *}
	<div class="related-exhibit-images">
		{foreach from=$relatedImages item=image}
			<a href="{$repositoryUrl}/{$image.image}" data-lightbox="related_images" {if $image.title}data-title="{$image.title}"{/if}>
				<img src="{$repositoryUrl}/{$image.thumbnail}" {if $image.shortTitle}alt="{$image.shortTitle}"{/if}/>
			</a>
		{/foreach}
	</div>
</div>
{/strip}
