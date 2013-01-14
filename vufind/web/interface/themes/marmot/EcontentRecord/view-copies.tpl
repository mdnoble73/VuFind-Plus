{strip}
{* Add availability as needed *}
{if $showAvailability && $availability && count($availability) > 0}
	<div>
		<table class="holdingsTable">
			<thead>
				<tr><th>Library</th><th>Owned</th><th>Available</th></tr>
			</thead>
			<tbody>
				{foreach from=$availability item=availabilityItem}
					<tr><td>{$availabilityItem->getLibraryName()}</td><td>{$availabilityItem->copiesOwned}</td><td>{$availabilityItem->availableCopies}</td></tr>
				{/foreach}
			</tbody>
		</table>
		<div class="note">
			{if strcasecmp($source, 'OverDrive') == 0}
				Note: Copies owned by the Digital library are available to patrons of any Marmot Library.  Titles owned by a specific library are only available for use by patrons of that library.
			{/if}
		</div>
	</div>
{/if}
{/strip}