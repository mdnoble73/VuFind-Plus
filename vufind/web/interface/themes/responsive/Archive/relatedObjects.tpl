{strip}
	<h2>{$label}</h2>
	{if $recordCount}
		{translate text="Showing"}
		<b> {$recordStart}</b> - <b>{$recordEnd} </b>
		{translate text='of'} <b> {$recordCount} </b>
		{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
	{/if}
	<div class="clearer"></div>
	<div class="results-covers home-page-browse-thumbnails">
		{foreach from=$relatedObjects item=image}
			<figure class="browse-thumbnail">
				<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if}>
					<img src="{$image.image}" {if $image.title}alt="{$image.title}"{/if}>
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$image.title}</strong>
				</figcaption>
			</figure>
		{/foreach}
	</div>

{/strip}