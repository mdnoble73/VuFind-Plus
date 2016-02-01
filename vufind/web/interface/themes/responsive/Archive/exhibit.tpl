{strip}
<div class="col-xs-12">
	{if $main_image}
		<div class="main-project-image">
			<img src="{$main_image}" class="img-responsive" usemap="#map"/>
		</div>
	{/if}

	<h2>
		{$title|escape}
	</h2>
	{$description}

	{* TODO: Figure out why the heck lightbox doesn't work *}
	<div class="related-exhibit-images">
		{foreach from=$relatedImages item=image}
			<figure class="thumbnail">
				<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if}>
					<img src="{$image.thumbnail}" {if $image.title}alt="{$image.title}"{/if}/>
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$image.title}</strong>
				</figcaption>
			</figure>
		{/foreach}
	</div>
</div>
{/strip}
