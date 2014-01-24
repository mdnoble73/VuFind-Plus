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
								<li >
									<a href="#" onclick="alert('Changing category to {$browseCategory->label}');">
										<div class="browse-category">
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
				<span class="selected-browse-label-text">New Fiction</span>
			</div>

			<div class="row" id="home-page-browse-thumbnails">
				{foreach from=$browseResults item="browseResult" name="browseResultsLoop"}
					<div class="col-xs-6 col-sm-4 col-md-3 col-lg-2 text-center">
						<div class="thumbnail">
							<a href="{$path}/GroupedWork/{$browseResult.id}/Home">
								<img class="hidden-xs hidden-sm visible-md" src="{$path}/bookcover.php?id={$browseResult.id}&size=medium&type=grouped_work&isn={$browseResult.isbn}">
								<img class="visible-xs visible-sm hidden-md hidden-lg" src="{$path}/bookcover.php?id={$browseResult.id}&size=small&type=grouped_work&isn={$browseResult.isbn}">
							</a>
							{include file="GroupedWork/title-rating.tpl" id=$browseResult.id showNotInterested=false}
						</div>
					</div>
					{* Insert separators at the appropriate locations *}
					{if $smarty.foreach.browseResultsLoop.iteration % 6 == 0}
						<div class="clearfix visible-lg"></div>
					{elseif $smarty.foreach.browseResultsLoop.iteration % 4 == 0}
						<div class="clearfix visible-md"></div>
					{elseif $smarty.foreach.browseResultsLoop.iteration % 3 == 0}
						<div class="clearfix visible-sm"></div>
					{/if}

				{/foreach}
			</div>
			<div class="row" id="more-browse-results">

			</div>
		</div>
	</div>
{/strip}
