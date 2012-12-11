{strip}
{if $user}
	<div data-role="navbar">
		<ul>
			<li><a rel="external" {if $pageTemplate=="checkedout.tpl" || $pageTemplate=="eContentCheckedOut.tpl" || $pageTemplate=="overDriveCheckedOut.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/CheckedOut">{translate text='Checked Out'}</a></li>
			<li><a rel="external" {if $pageTemplate=="availableHolds.tpl" || $pageTemplate=="eContentAvailableHolds.tpl" || $pageTemplate=="overDriveAvailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Holds?section=available">{translate text='Ready For Pickup'}</a></li>
			<li><a rel="external" {if $pageTemplate=="unavailableHolds.tpl" || $pageTemplate=="eContentUnavailableHolds.tpl" || $pageTemplate=="overDriveUnavailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Holds?section=unavailable">{translate text='On Hold'}</a></li>
			<li><a rel="external" {if $pageTemplate=="fines.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Fines">{translate text='Fines'}</a></li>
		</ul>
	</div> 
	{*create subsections as needed*}
	{if $pageTemplate=="checkedout.tpl" || $pageTemplate=="eContentCheckedOut.tpl" || $pageTemplate=="overDriveCheckedOut.tpl"}
		<div data-role="navbar">
			<ul>
				<li><a {if $pageTemplate=="checkedout.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/CheckedOut" data-role="button" rel="external" id="checkedOutPrint">{translate text='Books, Movies &amp; Music'} ({$profile.numCheckedOut})</a></li>
				{if $hasProtectedEContent}
					<li><a {if $pageTemplate=="eContentCheckedOut.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/EContentCheckedOut" data-role="button" rel="external" id="checkedOutEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentCheckedOut})</a></li>
				{/if}
				<li><a {if $pageTemplate=="overDriveCheckedOut.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/OverdriveCheckedOut" data-role="button" rel="external" id="checkedOutOverDrive">{translate text='OverDrive'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a></li>
			</ul>
		</div> 
	{elseif $pageTemplate=="availableHolds.tpl" || $pageTemplate=="eContentAvailableHolds.tpl" || $pageTemplate=="overDriveAvailableHolds.tpl"}
		<div data-role="navbar">
			<ul>
				<li><a {if $pageTemplate=="availableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Holds?section=available" data-role="button" rel="external" id="availablePrint">{translate text='Books, Movies &amp; Music'}  ({$profile.numHoldsAvailable})</a></li>
				{if $hasProtectedEContent}
					<li><a {if $pageTemplate=="eContentAvailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/EContentHolds?section=available" data-role="button" rel="external" id="availableEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentAvailableHolds})</a></li>
				{/if}
				<li><a {if $pageTemplate=="overDriveAvailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/OverdriveHolds?section=available" data-role="button" rel="external" id="availableOverDrive">{translate text='OverDrive'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a></li>
			</ul>
		</div> 
	{elseif $pageTemplate=="unavailableHolds.tpl" || $pageTemplate=="eContentUnavailableHolds.tpl" || $pageTemplate=="overDriveUnavailableHolds.tpl"}
		<div data-role="navbar">
			<ul>
				<li><a {if $pageTemplate=="unavailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Holds?section=unavailable" data-role="button" rel="external" id="availablePrint">{translate text='Books, Movies &amp; Music'}  ({$profile.numHoldsAvailable})</a></li>
				{if $hasProtectedEContent}
					<li><a {if $pageTemplate=="eContentUnavailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/EContentHolds?section=unavailable" data-role="button" rel="external" id="availableEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentAvailableHolds})</a></li>
				{/if}
				<li><a {if $pageTemplate=="overDriveUnavailableHolds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/OverdriveHolds?section=unavailable" data-role="button" rel="external" id="availableOverDrive">{translate text='OverDrive'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a></li>
			</ul>
		</div> 
	{/if}
{/if}
<script type="text/javascript">
getOverDriveSummary();
</script>
{/strip}