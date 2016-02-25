{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}
		<h2>
			{$title|escape}
		</h2>
		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				<div class="main-project-image">
					<img src="{$medium_image}" class="img-responsive"/>
				</div>
			</div>
			<div id="main-content" class="col-xs-8 col-sm-7 col-md-8 col-lg-9">
			</div>
		</div>

		<div class="jcarousel-wrapper">
			{*<a href="#" class="jcarousel-control-prev"*}{* data-target="-=1"*}{*><i class="glyphicon glyphicon-chevron-left"></i></a>*}
			<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

			<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
				<ul>
					{foreach from=$bookContents item=title}
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

		{if $description}
			<div class="row">
				<div class="result-label col-sm-4">Description: </div>
				<div class="col-sm-8 result-value">
					{$description}
				</div>
			</div>
		{/if}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
