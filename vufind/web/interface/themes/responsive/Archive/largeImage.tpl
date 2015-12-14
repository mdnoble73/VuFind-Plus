{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>
		{if $noImage}
			<div class="alert alert-warn">
				Sorry we could not find an image for this object.
			</div>
		{elseif $large_image}
			<div id="pika-openseadragon" class="openseadragon"></div>
		{else}
			<div class="main-project-image">
				<a href="{$image}">
					<img src="{$image}" class="img-responsive"/>
				</a>
			</div>
		{/if}


		{$description}
	</div>
	<script src="{$path}/js/openseadragon/openseadragon.js" ></script>
	<script src="{$path}/js/openseadragon/djtilesource.js" ></script>
	<script type="text/javascript">
		$(document).ready(function(){ldelim}
			{* var openSeadragonSettings = {ldelim}
					"pid":"{$pid}",
					"resourceUri":"{$large_image}",
					"tileSize":256,
					"tileOverlap":0,
					"settings": {ldelim}
							"id":"pika-openseadragon",
							"prefixUrl":"/js/openseadragon/images/",
							"debugMode":true,
							"djatokaServerBaseURL":"https://islandora.marmot.org/adore-djatoka/resolver",
							"animationTime":1.5,
							"blendTime":0.1,
							"alwaysBlend":false,
							"autoHideControls":1
							,"immediateRender":false,
							"wrapHorizontal":false,
							"wrapVertical":false,
							"wrapOverlays":false,
							"panHorizontal":1,
							"panVertical":1,
							"minZoomImageRatio":0.8,
							"maxZoomPixelRatio":2,
							"visibilityRatio":0.5,
							"springStiffness":5,
							"imageLoaderLimit":5,
							"clickTimeThreshold":300,
							"clickDistThreshold":5,
							"zoomPerClick":2,
							"zoomPerScroll":1.2,
							"zoomPerSecond":2,
							"showNavigator":1,
							"defaultZoomLevel":1
				{rdelim}
			{rdelim};
			var tileSource = new OpenSeadragon.DjatokaTileSource(
					"https://islandora.marmot.org/adore-djatoka/resolver",
					'{$large_image}',
					openSeadragonSettings
			);
			var viewer = OpenSeadragon({ldelim}
				id: "pika-openseadragon",
				preserveViewport: true,
				prefixUrl: '/js/openseadragon/images/',
				tileSource : tileSource
			{rdelim});
			VuFind.Archive.initializeOpenSeadragon(viewer); *}
			var viewer = OpenSeadragon({ldelim}
				id: "pika-openseadragon",
				prefixUrl: '/js/openseadragon/images/',
				tileSources : {ldelim}
					type: 'image',
					url: '{$image}',
					buildPyramid: true
				{rdelim}
			{rdelim});
		{rdelim});
	</script>
{/strip}
