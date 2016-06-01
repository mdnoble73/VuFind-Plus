
	<div class="col-xs-12">
		<h2>
			Colorado Digital Collection
		</h2>

		<div class="row">
			<div class="col-xs-12">
				<h3>Collections in the archive</h3>
				<div id="relatedProjectScroller" class="jcarousel-wrapper">
					<a href="#" class="jcarousel-control-prev"><i class="glyphicon glyphicon-chevron-left"></i></a>
					<a href="#" class="jcarousel-control-next"><i class="glyphicon glyphicon-chevron-right"></i></a>

					<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
						<ul>
							{foreach from=$relatedProjects item=project}
								<li class="relatedTitle">
									<a href="{$project.link}">
										<figure class="">
											<figcaption>{$project.title|removeTrailingPunctuation|truncate:80:"..."}</figcaption>
											<img src="{$project.image}" alt="{$project.title|removeTrailingPunctuation|truncate:80:"..."}|urlencode">
										</figure>
									</a>
								</li>
							{/foreach}
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12">
				<h3>Types of materials in the archive</h3>
				<div id="relatedContentTypesContainer" class="jcarousel-wrapper">
					<a href="#" class="jcarousel-control-prev"><i class="glyphicon glyphicon-chevron-left"></i></a>
					<a href="#" class="jcarousel-control-next"><i class="glyphicon glyphicon-chevron-right"></i></a>

					<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
						<ul>
							{foreach from=$relatedContentTypes item=contentType}
								<li class="relatedTitle">
									<a href="{$contentType.link}">
										<figure class="">
											<img src="{$contentType.image}" alt="{$contentType.title|removeTrailingPunctuation|truncate:80:"..."|urlencode}">
											<figcaption>{$contentType.title|removeTrailingPunctuation|truncate:80:"..."}</figcaption>
										</figure>
									</a>
								</li>
							{/foreach}
						</ul>
					</div>
				</div>
			</div>
		</div>

	</div>
