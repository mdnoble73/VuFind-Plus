{strip}
	<div id="home-page-browse-header" class="row">
		<div class="col-sm-12">
			<div class="row text-center" id="browse-label">
				<span class="browse-label-text">BROWSE THE CATALOG</span>
			</div>
			<div class="row text-center" id="browse-category-picker">
				<div class="jcarousel-wrapper">
					<div class="jcarousel">
						<ul>
							{foreach from=$browseCategories item=browseCategory}
								<li class="browse-category">
									<a href="#" onclick="VuFind.Browse.changeBrowseCategory('{$browseCategory->textId}');">
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
				<span class="selected-browse-label-text">{$browseResults.label}</span>
			</div>

			<div class="row" id="home-page-browse-thumbnails">
				{$browseResults.records}
			</div>
			<div class="row" id="more-browse-results">
				&dor;
			</div>
		</div>
	</div>
{/strip}
