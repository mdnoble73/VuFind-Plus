{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent">
			<div class="archiveComponentHeader">Browse By Location</div>
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<div id="exhibit-map">
					</div>
					<div id="exhibit-map-legend" class="">
						{/strip}
						{if $mapsBrowserKey}
							<script type="text/javascript">
								var infowindow;
								function initMap() {ldelim}
									VuFind.Archive.archive_map = new google.maps.Map(document.getElementById('exhibit-map'), {ldelim}
										center: {ldelim}lat: {$mapCenterLat}, lng: {$mapCenterLong}{rdelim},
										zoom: {$mapZoom}
										{rdelim});

									VuFind.Archive.archive_info_window = new google.maps.InfoWindow({ldelim}{rdelim});

									{foreach from=$mappedPlaces item=place name=place}
									{if $place.latitude && $place.longitude}
									var marker{$smarty.foreach.place.index} = new google.maps.Marker({ldelim}
										position: {ldelim}lat: {$place.latitude}, lng: {$place.longitude}{rdelim},
										map: VuFind.Archive.archive_map,
										title: '{$place.label} ({$place.count})',
										icon: {ldelim}
											path: google.maps.SymbolPath.CIRCLE,
											title: '{$place.count}',
											scale: {if $place.count > 999}35{elseif $place.count > 500}30{elseif $place.count > 250}25{elseif $place.count > 99}20{elseif $place.count > 49}17{elseif $place.count > 9}12{else}8{/if},
											strokeWeight: 2,
											strokeColor: 'white',
											strokeOpacity: 0.9,
											fillOpacity: 0.85,
											fillColor: 'DodgerBlue'
											{rdelim}
										{rdelim});

									VuFind.Archive.markers[{$smarty.foreach.place.index}] = marker{$smarty.foreach.place.index};
									marker{$smarty.foreach.place.index}.addListener('click', function(){ldelim}
										VuFind.Archive.handleMapClick({$smarty.foreach.place.index}, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label}', true);
										{rdelim});

									{if $selectedPlace == $place.pid}
									{* Click the first marker so we show images by default *}
									VuFind.Archive.handleMapClick({$smarty.foreach.place.index}, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label}', false);
									{/if}
									{/if}
									{/foreach}
								{rdelim}
							</script>
						{/if}
						{strip}
					</div>
					{if $mapsBrowserKey}
						<script src="https://maps.googleapis.com/maps/api/js?key={$mapsBrowserKey}&callback=initMap" async defer></script>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}