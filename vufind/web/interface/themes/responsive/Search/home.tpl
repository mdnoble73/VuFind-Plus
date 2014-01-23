{strip}
	<div id="home-page-browse-header" class="row">
		<div class="col-sm-12">
			<div class="row text-center" id="browse-label">
				<span class="browse-label-text">BROWSE THE CATALOG</span>
			</div>
			<div class="row text-center" id="browse-category-picker">
				{* Left Arrow *}
				<div class="col-sm-1 col-md-1 col-lg-1">
					<div class="browse-arrow">&lt;</div>
				</div>
				{* Browse Categories *}
				<div class="col-sm-10 col-md-10 col-lg-10">
					<div class="row">
						<div class="browse-category col-sm-4 col-md-4 col-lg-4">
							New Fiction
						</div>
						<div class="browse-category col-sm-4 col-md-4 col-lg-4">
							New Non-Fiction
						</div>
						<div class="browse-category col-sm-4 col-md-4 col-lg-4">
							New Movies
						</div>
					</div>
				</div>
				{* Right Arrow *}
				<div class="col-sm-1 col-md-1 col-lg-1">
					<div class="browse-arrow">&gt;</div>
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
							<a href="http://responsive2.marmot.org/GroupedWork/{$browseResult.id}/Home">
								<img class="hidden-xs hidden-sm visible-md" src="{$path}/bookcover.php?id={$browseResult.id}&size=medium&type=grouped_work&isn={$browseResult.isbn}">
								<img class="visible-xs visible-sm hidden-md hidden-lg" src="{$path}/bookcover.php?id={$browseResult.id}&size=small&type=grouped_work&isn={$browseResult.isbn}">
							</a>
							{include file="GroupedWork/title-rating.tpl" id=$browseResult.id}
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