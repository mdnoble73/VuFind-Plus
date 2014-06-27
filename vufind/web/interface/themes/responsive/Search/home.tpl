{strip}
	<div id="home-page-browse-header" class="row">
		<div class="col-sm-12">
			<div class="row text-center" id="browse-label">
				<span class="browse-label-text">BROWSE THE CATALOG</span>
			</div>
			<div class="row text-center" id="browse-category-picker">
				<div class="jcarousel-wrapper">
					<div class="jcarousel" id="browse-category-carousel">
						<ul>
							{foreach from=$browseCategories item=browseCategory name="browseCategoryLoop"}
								<li class="browse-category category{$smarty.foreach.browseCategoryLoop.index%9} {if $smarty.foreach.browseCategoryLoop.index == 0}selected{/if}" data-category-id="{$browseCategory->textId}" id="browse-category-{$browseCategory->textId}">
									<a href="#" onclick="return VuFind.Browse.changeBrowseCategory('{$browseCategory->textId}');">
										<div >
											{$browseCategory->label}
										</div>
									</a>
								</li>
							{/foreach}
						</ul>
					</div>

					<a href="#" class="jcarousel-control-prev">&lsaquo;</a>
					<a href="#" class="jcarousel-control-next">&rsaquo;</a>

					<p class="jcarousel-pagination"></p>
				</div>
			</div>
		</div>
	</div>
	<div id="home-page-browse-results" class="row">
		<div class="col-sm-12">
			<div class="row text-center" id="selected-browse-label">
				<div class="selected-browse-label-search"><a id="selected-browse-search-link" href="{$browseResults.searchUrl}"><span class="glyphicon glyphicon-search"></span> <span class="selected-browse-label-search-text">{$browseResults.label}</span></a></div>
			</div>


			<div class="row" id="home-page-browse-thumbnails">
				{$browseResults.records}
			</div>
			<a href="#" onclick = "return VuFind.Browse.getMoreResults();">
				<div class="row" id="more-browse-results">
					<img src="{img filename="browse_more_arrow.png"}" alt="Load More Browse Results" title="Load More Browse Results">
				</div>
			</a>
		</div>
	</div>
{/strip}
<script type="text/javascript">
	VuFind.Browse.curCategory = '{$browseResults.textId}';
</script>
