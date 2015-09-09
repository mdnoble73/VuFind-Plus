{strip}
	<div id="explore-more-header" class="row">Explore More</div>

	<div class="row">
		<div id="explore-more-body" class="col-xs-10 col-xs-offset-1">
			<div class="sectionHeader">Video</div>
			<div class="section">
				<a href="{$videoLink}">
					<img src="{$repositoryUrl}/{$videoImage}" alt="" width="100%">
				</a>
			</div>
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
			<div class="sectionHeader">Related Titles</div>
			<div id="explore-more-catalog" class="row">
				<div class="col-sm-12">
					{assign var="scrollerName" value="RelatedContent"}
					{assign var="scrollerTitle" value="Related Content"}
					{assign var="wrapperId" value="related-catalog-content"}
					{assign var="scrollerVariable" value="related-catalog-content"}
					{include file='ListWidget/titleScroller.tpl'}
				</div>
			</div>
		</div>
	</div>
	<br>
	<br>
{/strip}
<script type="text/javascript">
	$(document).ready(function(){ldelim}
		var relatedCatalogContentScroller = new TitleScroller('titleScrollerRelatedContent', 'RelatedContent', 'explore-more-catalog');
		relatedCatalogContentScroller.loadTitlesFrom('{$exploreMoreCatalogUrl}');
	{rdelim});
</script>