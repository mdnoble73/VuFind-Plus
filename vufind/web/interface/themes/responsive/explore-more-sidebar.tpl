{strip}
	<div id="explore-more-header" class="row">Explore More</div>

	<div class="row" id="explore-more-body">
		<div class="col-xs-10 col-xs-offset-1">
			{foreach from=$collections item=collection}
				<strong>{$collection.label}</strong>
				<div class="section">
					<a href="{$collection.link}"><img src="{$collection.image}" alt="{$collection.label}"></a>
				</div>
			{/foreach}

			{if $videoLink}
			<div class="sectionHeader">Video</div>
			<div class="section">
				<video width="100%" controls>
					<source src="{$videoLink}" type="video/mp4">
				</video>
			</div>
			{/if}
			<div class="sectionHeader">Related Content</div>
			<div class="section">
				{foreach from=$sectionList item=section}
					<div class="row">
						<div class="subsectionTitle col-xs-5">{$section.title}</div>
						<div class="subsection col-xs-5">
							<a href="{$section.link}"><img src="{$repositoryUrl}/{$section.image}" alt=""></a>
						</div>
					</div>

				{/foreach}
			</div>

			{* Related Titles Widget *}
			{if $related_titles.numFound > 0}
				<div class="sectionHeader">Related Titles</div>
				{* JCarousel with related titles *}
				{foreach from=$related_titles.topHits item=title}
					<div class="thumbnail">
						<a href="{$title.link}"><img class="img-responsive" src="{$title.cover}"/>{$title.title}</a>
					</div>
				{/foreach}
				<a href="{$related_titles.allResultsLink}">All Results ({$related_titles.numFound})</a>
			{/if}
		</div>
	</div>
	<br>
	<br>
{/strip}
