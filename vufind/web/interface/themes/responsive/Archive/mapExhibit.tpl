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

	<div class="lead">
		{if $thumbnail && !$main_image}
			<img src="{$thumbnail}" class="img-responsive thumbnail exhibit-thumbnail">
		{/if}
		{$description}
	</div>

	<div class="clear-both"></div>

	<div id="exhibit-map" class="">
	</div>
	<div id="exhibit-map-legend" class="">
		{/strip}
		{if $mapsBrowserKey}
			<script type="text/javascript">
				var infowindow;
				function initMap() {ldelim}
					VuFind.Archive.archive_map = new google.maps.Map(document.getElementById('exhibit-map'), {ldelim}
							center: {ldelim}lat: {$mapCenterLat}, lng: {$mapCenterLong}{rdelim},
							zoom: 10
					{rdelim});

					VuFind.Archive.archive_info_window = new google.maps.InfoWindow({ldelim}{rdelim});

					{foreach from=$mappedPlaces item=place name=place}
						{if $place.latitude && $place.longitude}
							var marker{$smarty.foreach.place.index} = new google.maps.Marker({ldelim}
								position: {ldelim}lat: {$place.latitude}, lng: {$place.longitude}{rdelim},
								map: VuFind.Archive.archive_map,
								title: '{$place.label}'
							{rdelim});

							marker{$smarty.foreach.place.index}.addListener('click', function(){ldelim}
								VuFind.Archive.handleMapClick(marker{$smarty.foreach.place.index}, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label}');

							{rdelim});
						{/if}
					{/foreach}
				{rdelim}
			</script>
		{/if}
		{strip}
		{*
		<ol>
		{foreach from=$mappedPlaces item=place}
			<li>
				<a href="{$place.url}">
					{$place.label} {$place.pid} ({$place.latitude}, {$place.longitude}) has {$place.count} objects
				</a>
			</li>
		{/foreach}
		</ol>
		*}
	</div>

	<div id="related-objects-for-exhibit">
		Click any location to view more information about that location.
	</div>

	{if $repositoryLink && $user && ($user->hasRole('archives') || $user->hasRole('opacAdmin'))}
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
	{if $mapsBrowserKey}
		<script src="https://maps.googleapis.com/maps/api/js?key={$mapsJsKey}&callback=initMap" async defer></script>
	{/if}
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>