{strip}
	{if $page == 1 && $reloadHeader == 1}
		<h2>{$label}</h2>
		<div class="row">
			<div class="col-sm-4">
				{if $recordCount}
					{$recordCount} objects for this location.
				{/if}
			</div>
			<div class="col-sm-4 col-sm-offset-4">
				{* Display information to sort the results (by date or by title *}
				<select id="results-sort" name="sort" onchange="VuFind.Archive.sort = this.options[this.selectedIndex].value;VuFind.Archive.reloadMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}', 0);" class="form-control">
					<option value="title" {if $sort=='title'}selected="selected"{/if}>{translate text='Sort by ' }Title</option>
					<option value="newest" {if $sort=='newest'}selected="selected"{/if}>{translate text='Sort by ' }Newest First</option>
					<option value="oldest" {if $sort=='oldest'}selected="selected"{/if}>{translate text='Sort by ' }Oldest First</option>
				</select>
			</div>
		</div>

		{if $recordEnd < $recordCount}
			{* Display selection of date ranges *}
			<div class="row">
				<div class="col-xs-12">
					<div class="btn-group btn-group-sm" role="group" aria-label="Select Dates" data-toggle="buttons">
						{if $numObjectsWithUnknownDate}
							<label class="btn btn-default">
								<input name="dateFilter" onchange="VuFind.Archive.reloadMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}', 0)" type="checkbox" autocomplete="off" value="{$facet.value}">Unknown ({$numObjectsWithUnknownDate})
							</label>
						{/if}
						{foreach from=$dateFacetInfo item=facet}
							<label class="btn btn-default btn-sm">
								<input name="dateFilter" onchange="VuFind.Archive.reloadMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}', 0)" type="checkbox" autocomplete="off" value="{$facet.value}">{$facet.label} ({$facet.count})
							</label>
						{/foreach}
					</div>
				</div>
			{/if}
		</div>

		<div class="clearer"></div>
		<div id="results">
	{/if}

	{if $solrError}
		<div class="alert alert-danger">{$solrError}</div>
		<a href="{$solrLink}">Link to solr query</a>
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
	{if $page == 1 && $reloadHeader == 1}
		</div>
		{if $recordEnd < $recordCount}
			<a onclick="return VuFind.Archive.getMoreMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}')">
				<div class="row" id="more-browse-results">
					<img src="{img filename="browse_more_arrow.png"}" alt="Load More Search Results" title="Load More Search Results">
				</div>
			</a>
		{/if}
	{/if}
	</div>

{/strip}