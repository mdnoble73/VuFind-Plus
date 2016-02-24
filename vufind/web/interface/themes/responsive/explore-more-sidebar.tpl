{strip}
	<div id="explore-more-header" class="row">Explore More</div>

	<div id="explore-more-body" class="row"> {* To Get use of the full width there is*}
		{*<div class="col-xs-10*}{* col-xs-offset-1*}{*">*}
		{foreach from=$collections item=collection}
			<strong>{$collection.label}</strong>
			<div class="section">
				<a href="{$collection.link}"><img src="{$collection.image}" alt="{$collection.label}" class="img-responsive img-thumbnail"></a>
			</div>
		{/foreach}

		{if count($relatedArchiveData) > 0}
			<div class="sectionHeader">Related Content</div>
			{foreach from=$relatedArchiveData item=section}
				<div class="section">

					<div class="row">
						<div class="subsectionTitle col-xs-5">{$section.title}</div>
						<div class="subsection col-xs-5">
							<a href="{$section.link}"><img src="{$section.thumbnail}" alt="{$section.description}" class="img-responsive img-thumbnail"></a>
						</div>
					</div>
				</div>
			{/foreach}
		{/if}

		{* Related Titles Widget *}
		{if $related_titles.numFound > 0}
			<div class="sectionHeader">Related Titles</div>
			{* JCarousel with related titles *}
			<div class="jcarousel-wrapper">
				{*<a href="#" class="jcarousel-control-prev"*}{* data-target="-=1"*}{*><i class="glyphicon glyphicon-chevron-left"></i></a>*}
				<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

				<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
					<ul>
						{foreach from=$related_titles.topHits item=title}
							<li class="relatedTitle">
								<a href="{$title.link}">
									<figure class="thumbnail">
										<img src="{$title.cover}" alt="{$title.title|removeTrailingPunctuation|truncate:80:"..."}">
										<figcaption>{$title.title|removeTrailingPunctuation|truncate:80:"..."}</figcaption>
									</figure>
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>

			<a href="{$related_titles.allResultsLink}">All Results ({$related_titles.numFound})</a>
		{/if}

		{* More Like This *}
		{if $showMoreLikeThisInExplore}
			{include file="GroupedWork/exploreMoreLikeThis.tpl"}
		{/if}

		{foreach from=$exploreMoreInfo item=exploreMoreOption}
			<div class="sectionHeader">{$exploreMoreOption.label}</div>
			<div class="col-sm-12">
				{$exploreMoreOption.body}
			</div>
		{/foreach}

		{* Related Articles Widget *}
		{if $relatedArticles}
			<div class="sectionHeader">Related Articles</div>
			<div class="center-block">
				<a href="{$relatedArticles.link}">
					<img src="{$relatedArticles.thumbnail}" alt="{$relatedArticles.description|escape}" class="img-responsive center-block">
					{$relatedArticles.title}
				</a>
			</div>

		{/if}
	</div>
{/strip}
