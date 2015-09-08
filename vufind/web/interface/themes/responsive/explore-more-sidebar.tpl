{strip}
	<div id="explore-more-header" class="row">Explore More</div>

	<div class="row">
		<div id="explore-more-body" class="col-xs-10 col-xs-offset-1">
			{if $exploreMoreMainLinks}
				<div id="explore-more-links">
					{foreach from=$exploreMoreMainLinks item=mainLink}
						<a href="{$mainLink.url}"> <div class="col-xs-12 explore-more-main-link">{$mainLink.title}</div></a>
					{/foreach}
				</div>
			{/if}

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
	<br/>
	<br/>
{/strip}
<script type="text/javascript">
	$(document).ready(function(){ldelim}
		var relatedCatalogContentScroller = new TitleScroller('titleScrollerRelatedContent', 'RelatedContent', 'explore-more-catalog');
		relatedCatalogContentScroller.loadTitlesFrom('{$exploreMoreCatalogUrl}');
	{rdelim});
</script>