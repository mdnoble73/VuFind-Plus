{strip}
<script type="text/javascript" src="{$path}/js/overdrive.js"></script>
<script type="text/javascript" src="{$path}/js/scripts.js"></script>
<div data-role="page" id="MyResearch-checkedout">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user->cat_username}
			<h3>{translate text='My Account'}</h3>
			<h4>{translate text="Checked Out"}</h4>
			<div data-role="controlgroup">
				<a href="{$path}/MyResearch/CheckedOut" data-role="button" rel="external" id="checkedOutPrint">{translate text='Books, Movies &amp; Music'} ({$profile.numCheckedOut})</a>
				<a href="{$path}/MyResearch/EContentCheckedOut" data-role="button" rel="external" id="checkedOutEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentCheckedOut})</a>
				<a href="{$path}/MyResearch/OverdriveCheckedOut" data-role="button" rel="external" id="checkedOutOverDrive">{translate text='OverDrive'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a>
			</div>
			
			<h4>{translate text="Ready For Pickup"}</h4>
			<div data-role="controlgroup">
				<a href="{$path}/MyResearch/Holds?section=available" data-role="button" rel="external" id="availablePrint">{translate text='Books, Movies &amp; Music'}  ({$profile.numHoldsAvailable})</a>
				{if $hasProtectedEContent}
				<a href="{$path}/MyResearch/EContentHolds?section=available" data-role="button" rel="external" id="availableEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentAvailableHolds})</a>
				{/if}
				<a href="{$path}/MyResearch/OverdriveHolds?section=available" data-role="button" rel="external" id="availableOverDrive">{translate text='OverDrive'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a>
			</div>
			
			<h4>{translate text="On Hold"}</h4>
			<div data-role="controlgroup">
				<a href="{$path}/MyResearch/Holds?section=unavailable" data-role="button" rel="external" id="unavailablePrint">{translate text='Books, Movies &amp; Music'} ({$profile.numHoldsRequested})</a>
				{if $hasProtectedEContent}
				<a href="{$path}/MyResearch/EContentHolds?section=unavailable" data-role="button" rel="external" id="unavailableEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentUnavailableHolds})</a>
				{/if}
				<a href="{$path}/MyResearch/OverdriveHolds?section=unavailable" data-role="button" rel="external" id="unavailableOverDrive">{translate text='OverDrive'} (<span id="unavailableHoldsOverDrivePlaceholder">?</span>)</a>
			</div>
			
		{else}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
	{include file="footer.tpl"}
</div>
<script type="text/javascript">
getOverDriveSummary();
</script>
{/strip}