{strip}
	<form role="from">
		<div class="form-group">
			<label for="selectLibrary">Select a Location</label>
			<select name="selectLibrary" id="selectLibrary" onchange="return VuFind.showLocationHoursAndMap();">
				{foreach from=$libraryLocations item=curLocation}
					<option value="{$curLocation.id}">{$curLocation.name}</option>
				{/foreach}
			</select>
		</div>
	</form>
	{foreach from=$libraryLocations item=curLocation name=locationLoop}
		<div class="locationInfo container" id="locationAddress{$curLocation.id}" {if !$smarty.foreach.locationLoop.first}style="display:none"{/if}>
			<div class="row">
				<h3>{$curLocation.name}</h3>
			</div>
			<div class="row">
				<div class="col-xs-3">
					<dl>
						<dt>Address</dt>
						<dd>
							{$curLocation.address}
						</dd>
						<dt>Phone</dt>
						<dd>{$curLocation.phone}</dd>
					</dl>
				</div>
				<div class="col-xs-9">
					<a href="{$curLocation.map_link}"><img src="{$curLocation.map_image}" alt="Map"></a>
					<br/><a href="{$curLocation.map_link}">Directions</a>
				</div>
			</div>
			{if $curLocation.hours}
				<h4>Hours</h4>
				{foreach from=$curLocation.hours item=curHours}
					<div class="row">
						<div class="col-xs-4">
							{if $curHours->day == 0}
								Sunday
							{elseif $curHours->day == 1}
								Monday
							{elseif $curHours->day == 2}
								Tuesday
							{elseif $curHours->day == 3}
								Wednesday
							{elseif $curHours->day == 4}
								Thursday
							{elseif $curHours->day == 5}
								Friday
							{elseif $curHours->day == 6}
								Saturday
							{/if}
						</div>
						<div class="col-xs-8 text-left">
							{if $curHours->closed}
								Closed
							{else}
								{$curHours->open} - {$curHours->close}
							{/if}
						</div>
					</div>
				{/foreach}
			{/if}
		</div>
	{/foreach}
{/strip}