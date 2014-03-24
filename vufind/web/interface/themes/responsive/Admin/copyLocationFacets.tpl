	<div id="main-content">
		<h1>Copy Location Facets</h1>
		{if count($allLocations) == 0}
			<div>Sorry, there are no locations available for you to copy facets from.</div>
		{else}
			<form action="/Admin/Locations" method="get">
				<div>
					<input type="hidden" name="id" value="{$id}"/>
					<input type="hidden" name="objectAction" value="copyFacetsFromLocation"/>
					<label for="locationToCopyFrom">Select a location to copy facets from:</label>
					<select id="locationToCopyFrom" name="locationToCopyFrom">
						{foreach from=$allLocations item=location}
							<option value="{$location->locationId}">{$location->displayName}</option>
						{/foreach}
					</select>
					<input type="submit" name="submit" value="Copy Facets"/>
				</div>
			</form>
		{/if}
	</div>
