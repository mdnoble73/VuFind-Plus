{strip}
	{if $page == 1}
		<h2>{$label}</h2>
		{if $recordCount}
			{translate text="Showing"}
			<b> {$recordStart}</b> - <b>{$recordEnd} </b>
			{translate text='of'} <b> {$recordCount} </b>
			{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
		{/if}
		{* Display information to sort the results (by date or by title *}
		<select id="results-sort" name="sort" onchange="VuFind.Archive.sort = this.options[this.selectedIndex].value;VuFind.Archive.reloadMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}');" class="input-medium">
			<option value="title" {if $sort=='title'}selected="selected"{/if}>{translate text='Sort by ' }Title</option>
			<option value="newest" {if $sort=='newest'}selected="selected"{/if}>{translate text='Sort by ' }Newest First</option>
			<option value="oldest" {if $sort=='oldest'}selected="selected"{/if}>{translate text='Sort by ' }Oldest First</option>
		</select>

		<div class="clearer"></div>
	{/if}
	<div class="results-covers home-page-browse-thumbnails">
		{foreach from=$relatedObjects item=image}
			<figure class="browse-thumbnail">
				<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if}>
					<img src="{$image.image}" {if $image.title}alt="{$image.title}"{/if}>
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$image.title} ({$image.dateCreated})</strong>
				</figcaption>
			</figure>
		{/foreach}
	</div>

	<div id="nextInsertPoint"></div>
	{if $page == 1}
		{if $recordEnd < $recordCount}
			<a onclick="return VuFind.Archive.getMoreMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}')">
				<div class="row" id="more-browse-results">
					<img src="{img filename="browse_more_arrow.png"}" alt="Load More Search Results" title="Load More Search Results">
				</div>
			</a>
		{/if}
	{/if}

{/strip}