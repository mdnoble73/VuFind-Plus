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
									{*<a*}{* href="#"  causes the page to bounce to the top, and is very jarring. *}{*>*}{* js now implemented through browse.js. These links can be removed once styling referencing it is adjusted. plb 12-22-2014 *}
									{* links removed 4-23-2015 *}
										<div >
											{$browseCategory->label}
										</div>
									{*</a>*}
								</li>
							{/foreach}
						</ul>
					</div>
{* indicators arrows moved to css:
 #browse-category-picker a.jcarousel-control-next:after and
  #browse-category-picker a.jcarousel-control-prev:before
  plb 11-18-2014 *}
					<a href="#" class="jcarousel-control-prev">{*&lsaquo;*}</a>
					<a href="#" class="jcarousel-control-next">{*&rsaquo;*}</a>

					<p class="jcarousel-pagination"></p>
				</div>
			</div>
		</div>
	</div>
	<div id="home-page-browse-content" class="row"> {* id renamed *}
		<div class="col-sm-12">

			<div class="row text-center" id="selected-browse-label">

				<div class="btn-group btn-group-sm" data-toggle="buttons">
					<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="VuFind.Browse.toggleBrowseMode(this.id)" type="radio" id="covers">
						<span class="thumbnail-icon"></span><span> Covers</span>
					</label>
					<label for="lists" title="Lists" class="btn btn-sm btn-default"><input onchange="VuFind.Browse.toggleBrowseMode(this.id);" type="radio" id="lists">
						<span class="list-icon"></span><span> Lists</span>
					</label>
				</div>

				<div class="selected-browse-label-search">
					<a id="selected-browse-search-link" title="See the search results page for this browse category">
						<span class="icon-before"></span> {*space needed for good padding between text and icon *}
						<span class="selected-browse-label-search-text"></span>
						<span class="icon-after"></span>
					</a>
				</div>
			</div>

			<div id="home-page-browse-results">
				<div class="row">
				</div>
			</div>


			<a href="#" onclick="return VuFind.Browse.getMoreResults()">
				<div class="row" id="more-browse-results">
					<img src="{img filename="browse_more_arrow.png"}" alt="Load More Browse Results" title="Load More Browse Results">
				</div>
			</a>
		</div>
	</div>
{/strip}
<script type="text/javascript">
	$(function(){ldelim}
		{*VuFind.Browse.curCategory = '*}{*/literal*}{*{$browseResults.textId}*}{*literal*}{*';*}
		{if !$onInternalIP}
		VuFind.Browse.browseMode = VuFind.Account.hasLocalStorage() ? window.localStorage.getItem('browseMode') : '{$browseMode}';
		{else}
		VuFind.Browse.browseMode = '{$browseMode}';
		VuFind.Browse.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+VuFind.Browse.browseMode).parent('label').addClass('active'); {* show user which one is selected *}
		VuFind.Browse.toggleBrowseMode();
	{rdelim});
</script>
