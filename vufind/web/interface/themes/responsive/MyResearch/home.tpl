{strip}
{if (isset($title)) }
	<script type="text/javascript">
		alert("{$title}");
	</script>
{/if}
<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
	<div id="main-content" class="col-md-9">
		<div data-role="content">
			{if $user->cat_username}
				{if $profile.web_note}
					<div id="web_note" class="text-info text-center well well-small"><strong>{$profile.web_note}</strong></div>
				{/if}

				<h3>{translate text='My Account'}</h3>
				<h4>{translate text="Checked Out"}</h4>
				<div class="btn-group">
					<a href="{$path}/MyResearch/CheckedOut" id="checkedOutPrint" class="btn">{translate text='Books, Movies &amp; Music'} ({$profile.numCheckedOut})</a>
					<a href="{$path}/MyResearch/EContentCheckedOut" id="checkedOutEContent" class="btn">{translate text='eBooks and eAudio'} ({$profile.numEContentCheckedOut})</a>
					<a href="{$path}/MyResearch/OverdriveCheckedOut" id="checkedOutOverDrive" class="btn">{translate text='OverDrive'} (<span class="checkedOutItemsOverDrivePlaceholder">?</span>)</a>
				</div>

				<h4>{translate text="Ready For Pickup"}</h4>
				<div class="btn-group">
					<a href="{$path}/MyResearch/Holds?section=available" id="availablePrint" class="btn">{translate text='Books, Movies &amp; Music'}  ({$profile.numHoldsAvailable})</a>
					{if $hasProtectedEContent}
						<a href="{$path}/MyResearch/EContentHolds?section=available" id="availableEContent" class="btn">{translate text='eBooks and eAudio'} ({$profile.numEContentAvailableHolds})</a>
					{/if}
					<a href="{$path}/MyResearch/OverdriveHolds?section=available" id="availableOverDrive" class="btn">{translate text='OverDrive'} (<span class="availableHoldsOverDrivePlaceholder">?</span>)</a>
				</div>

				<h4>{translate text="On Hold"}</h4>
				<div class="btn-group">
					<a href="{$path}/MyResearch/Holds?section=unavailable" id="unavailablePrint" class="btn">{translate text='Books, Movies &amp; Music'} ({$profile.numHoldsRequested})</a>
					{if $hasProtectedEContent}
						<a href="{$path}/MyResearch/EContentHolds?section=unavailable" id="unavailableEContent" class="btn">{translate text='eBooks and eAudio'} ({$profile.numEContentUnavailableHolds})</a>
					{/if}
					<a href="{$path}/MyResearch/OverdriveHolds?section=unavailable" id="unavailableOverDrive" class="btn">{translate text='OverDrive'} (<span class="unavailableHoldsOverDrivePlaceholder">?</span>)</a>
				</div>

			{else}
				You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
			{/if}
		</div>
	</div>
	<script type="text/javascript">
		VuFind.OverDrive.getOverDriveSummary();
	</script>
</div>
{/strip}