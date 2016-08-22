{strip}
<div class="col-xs-12">
	{if $main_image}
		<div class="main-project-image">
			<img src="{$main_image}" class="img-responsive" usemap="#map">
		</div>
	{/if}

	<h2>
		{$title|escape}
	</h2>

	<div class="lead row">
		{if $thumbnail && !$main_image}
			<img src="{$thumbnail}" class="img-responsive thumbnail exhibit-thumbnail">
		{/if}
		{$description}
	</div>

	<div class="clear-both"></div>

	{if $showWidgetView}
		<div id="related-exhibit-images" class="exploreMoreBar row">
			<div class="label-top">
				<div class="exploreMoreBarLabel">{translate text='Categories'}</div>
			</div>
			<div class="exploreMoreContainer">
				<div class="jcarousel-wrapper">
					{* Scrolling Buttons *}
					<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
					<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

					<div class="exploreMoreItemsContainer jcarousel"{* data-wrap="circular" data-jcarousel="true"*}> {* noIntialize is a filter for VuFind.initCarousels() *}
						<ul>
							{foreach from=$relatedImages item=image}
								<li class="explore-more-option">
									<figure class="thumbnail" title="{$image.title|escape}">
										<div class="explore-more-image">
											<a href='{$image.link}'>
												<img src="{$image.image}" alt="{$image.title|escape}">
											</a>
										</div>
										<figcaption class="explore-more-category-title">
											<strong>{$image.title|truncate:30}</strong>
										</figcaption>
									</figure>
								</li>
							{/foreach}
						</ul>
					</div>
				</div>
			</div>
		</div>
	{else}
		<div id="related-exhibit-images" class="{if $showThumbnailsSorted}row{else}{if count($relatedImages) >= 18}results-covers home-page-browse-thumbnails{else}browse-thumbnails-few{/if}{/if}">
			{foreach from=$relatedImages item=image}
				{if $showThumbnailsSorted}<div class="col-xs-6 col-sm-4 col-md-3">{/if}
					<figure class="{if $showThumbnailsSorted}browse-thumbnail-sorted{else}browse-thumbnail{/if}">
						<a href="{$image.link}" {if $image.title}data-title="{$image.title}"{/if}>
							<img src="{$image.image}" {if $image.title}alt="{$image.title}"{/if}>
						</a>
						<figcaption class="explore-more-category-title">
							<strong>{$image.title}</strong>
						</figcaption>
					</figure>
				{if $showThumbnailsSorted}</div>{/if}
			{/foreach}
		</div>
	{/if}

	{if $repositoryLink && $user && ($user->hasRole('archives') || $user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
		<div id="more-details-accordion" class="panel-group">
			<div class="panel {*active*}{*toggle on for open*}" id="staffViewPanel">
				<a href="#staffViewPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Staff View
						</div>
					</div>
				</a>
				<div id="staffViewPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
							View in Islandora
						</a>
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
							View MODS Record
						</a>
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/edit" target="_blank">
							Edit MODS Record
						</a>
					</div>
				</div>
			</div>
		</div>
	{/if}
</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>