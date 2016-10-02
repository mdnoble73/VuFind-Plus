{strip}
<div class="col-xs-12">
	{if $main_image}
		<div class="main-project-image">
			<img src="{$main_image}" class="img-responsive" usemap="#map">
		</div>
	{/if}

	<h2>
		{$title|escape}
	</h2>

	<div class="lead row">
		{if $hasImageMap}
			{$imageMap}
			<script type="text/javascript">
				$(document).ready(function(e) {ldelim}
					$('img[usemap]').addClass('img-responsive');
					$('img[usemap]').rwdImageMaps();
				{rdelim});
			</script>
		{else}
			{if $thumbnail && !$main_image}
				<img src="{$thumbnail}" class="img-responsive thumbnail exhibit-thumbnail">
			{/if}
		{/if}
		{$description}
	</div>

	<div class="clear-both"></div>

	{if $showWidgetView}
		<div id="related-exhibit-images" class="exploreMoreBar row">
			<div class="label-top">
				<div class="exploreMoreBarLabel">{translate text='Categories'}</div>
			</div>
			<div class="exploreMoreContainer">
				<div class="jcarousel-wrapper">
					{* Scrolling Buttons *}
					<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
					<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

					<div class="exploreMoreItemsContainer jcarousel"{* data-wrap="circular" data-jcarousel="true"*}> {* noIntialize is a filter for VuFind.initCarousels() *}
						<ul>
							{foreach from=$relatedImages item=image}
								<li class="explore-more-option">
									<figure class="thumbnail" title="{$image.title|escape}">
										<div class="explore-more-image">
											<a href='{$image.link}'>
												<img src="{$image.image}" alt="{$image.title|escape}">
											</a>
										</div>
										<figcaption class="explore-more-category-title">
											<strong>{$image.title|truncate:30}</strong>
										</figcaption>
									</figure>
								</li>
							{/foreach}
						</ul>
					</div>
				</div>
			</div>
		</div>
	{else}
		{* Standard View a la Browse Categories*}
		<div class="row">

			<div class="col-sm-6">
				<form action="/Archive/Results">
					<div class="input-group">
						<input type="text" name="lookfor" size="30" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." autocomplete="off" class="form-control" placeholder="Search this collection">
						<div class="input-group-btn" id="search-actions">
							<button class="btn btn-default" type="submit">GO</button>
						</div>
						<input type="hidden" name="islandoraType" value="IslandoraKeyword"/>
						{if count($subCollections) > 0}
						<input type="hidden" name="filter[]" value='RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/{$pid}"{foreach from=$subCollections item=subCollectionPID} OR RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/{$subCollectionPID}"{/foreach}'/>
						{else}
						<input type="hidden" name="filter[]" value='RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/{$pid}"'/>
						{/if}
					</div>
				</form>
			</div>
			<div class="col-sm-4 col-sm-offset-2">
				{* Display information to sort the results (by date or by title *}
				<select id="results-sort" name="sort" onchange="VuFind.Archive.sort = this.options[this.selectedIndex].value;VuFind.Archive.reloadMapResults('{$exhibitPid|urlencode}', '{$placePid|urlencode}', 0);" class="form-control">
					<option value="title" {if $sort=='title'}selected="selected"{/if}>{translate text='Sort by ' }Title</option>
					<option value="newest" {if $sort=='newest'}selected="selected"{/if}>{translate text='Sort by ' }Newest First</option>
					<option value="oldest" {if $sort=='oldest'}selected="selected"{/if}>{translate text='Sort by ' }Oldest First</option>
				</select>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				{if $recordCount}
					{$recordCount} objects in this collection.
				{/if}
			</div>
		</div>
		<div id="related-exhibit-images" class="{if $showThumbnailsSorted && count($relatedImages) >= 18}row{elseif count($relatedImages) >= 18}results-covers home-page-browse-thumbnails{else}browse-thumbnails-few{/if}">
			{foreach from=$relatedImages item=image}
				{if $showThumbnailsSorted && count($relatedImages) >= 18}<div class="col-xs-6 col-sm-4 col-md-3">{/if}
					<figure class="{if $showThumbnailsSorted && count($relatedImages) >= 18}browse-thumbnail-sorted{else}browse-thumbnail{/if}">
						<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if} onclick="return VuFind.Archive.showObjectInPopup('{$image.pid|urlencode}')">
							<img src="{$image.image}" {if $image.title}alt="{$image.title}"{/if}>
							<figcaption class="explore-more-category-title">
								<strong>{$image.title}</strong>
							</figcaption>
						</a>
					</figure>
				{if $showThumbnailsSorted && count($relatedImages) >= 18}</div>{/if}
			{/foreach}
		</div>

		{* Show more link if we aren't seeing all the records already *}
		<div id="nextInsertPoint">
		{if $recordEnd < $recordCount}
			<a onclick="return VuFind.Archive.getMoreExhibitResults('{$pid|urlencode}')">
				<div class="row" id="more-browse-results">
					<img src="{img filename="browse_more_arrow.png"}" alt="Load More Results" title="Load More Results">
				</div>
			</a>
		{/if}
		</div>
	{/if}

	{if $repositoryLink && $user && ($user->hasRole('archives') || $user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
		<div id="more-details-accordion" class="panel-group">
			<div class="panel {*active*}{*toggle on for open*}" id="staffViewPanel">
				<a href="#staffViewPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Staff View
						</div>
					</div>
				</a>
				<div id="staffViewPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
							View in Islandora
						</a>
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
							View MODS Record
						</a>
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/edit" target="_blank">
							Edit MODS Record
						</a>
					</div>
				</div>
			</div>
		</div>
	{/if}
</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
	{rdelim});
</script>