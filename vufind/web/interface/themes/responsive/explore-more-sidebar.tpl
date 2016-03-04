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

		{if count($exactEntityMatches) > 0}
			<div class="sectionHeader">People, Places &amp; Events</div>
			<div class="section">
				{foreach from=$exactEntityMatches item=entity}
					<strong>{$entity.title}</strong>
					<div class="section">
						<a href="{$entity.link}"><img src="{$entity.thumbnail}" alt="{$entity.description}" class="img-responsive img-thumbnail"></a>
					</div>
				{/foreach}
			</div>
		{/if}

		{* More Like This *}
		{if $showMoreLikeThisInExplore}
			{include file="GroupedWork/exploreMoreLikeThis.tpl"}
		{/if}

		{if count($relatedArchiveData) > 0}
			<div class="sectionHeader">From the Archive</div>
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
			<div class="sectionHeader">Books and More</div>
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

		{* Related Articles Widget *}
		{if $relatedArticles}
			<div class="sectionHeader">Articles and More</div>
			<div class="section">
				{foreach from=$relatedArticles item=section}
				<div class="row">
					<a href="{$section.link}">
						<div class="subsection col-xs-5">
							<img src="{$section.thumbnail}" alt="{$section.description}" class="img-responsive img-thumbnail">
						</div>
						<div class="subsectionTitle col-xs-5">{$section.title}</div>
					</a>
				</div>
				{/foreach}
			</div>
		{/if}

		{* Sections for Related Content From Novelist  *}
		{foreach from=$exploreMoreInfo item=exploreMoreOption}
			<div class="sectionHeader"{if $exploreMoreOption.hideByDefault} style="display: none;"{/if}>{$exploreMoreOption.label}</div>
			<div class="{*col-sm-12 *}jcarousel-wrapper"{if $exploreMoreOption.hideByDefault} style="display: none;"{/if}>
				<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>
				{$exploreMoreOption.body}
			</div>
		{/foreach}
	</div>
{/strip}
