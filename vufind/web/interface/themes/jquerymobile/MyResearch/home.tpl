<script type="text/javascript" src="{$path}/js/overdrive.js"></script>
<script type="text/javascript" src="{$path}/js/scripts.js"></script>
<div data-role="page" id="MyResearch-checkedout">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user->cat_username}
			<h3>{translate text='My Account'}</h3>
			<h4>Print Titles</h4>
			<div data-role="controlgroup">
				<a href="{$path}/MyResearch/CheckedOut" data-role="button" rel="external">{translate text='Checked Out Items'}{if $profile.numCheckedOut} ({$profile.numCheckedOut}){/if}</a>
				<a href="{$path}/MyResearch/Holds" data-role="button" rel="external">{translate text='Available Holds'}{if $profile.numHoldsAvailable} ({$profile.numHoldsAvailable}){/if}</a>
      	<a href="{$path}/MyResearch/Holds" data-role="button" rel="external">{translate text='Unavailable Holds'}{if $profile.numHoldsRequested} ({$profile.numHoldsRequested}){/if}</a>
			</div>
			<h4>eContent Titles</h4>
			<div data-role="controlgroup">
	    	<a href="{$path}/MyResearch/EContentCheckedOut" data-role="button" rel="external">{translate text='Checked Out Items'} ({$profile.numEContentCheckedOut})</a>
	    	{if $hasProtectedEContent}
	    	<a href="{$path}/MyResearch/EContentHolds" data-role="button" rel="external">{translate text='Available Holds'} ({$profile.numEContentAvailableHolds})</a>
	    	<a href="{$path}/MyResearch/EContentHolds" data-role="button" rel="external">{translate text='Unavailable Holds'} ({$profile.numEContentUnavailableHolds})</a>
	    	<a href="{$path}/MyResearch/MyEContentWishlist" data-role="button" rel="external">{translate text='Wish List'} ({$profile.numEContentWishList})</a>
	    	{/if}
	    </div>
	    <h4>OverDrive Titles</h4>
	    <div data-role="controlgroup">
	    	<a href="{$path}/MyResearch/OverdriveCheckedOut" data-role="button" rel="external">{translate text='Checked Out Items'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a>
	    	<a href="{$path}/MyResearch/OverdriveHolds" data-role="button" rel="external">{translate text='Available Holds'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a>
	    	<a href="{$path}/MyResearch/OverdriveHolds" data-role="button" rel="external">{translate text='Unavailable Holds'} (<span id="unavailableHoldsOverDrivePlaceholder">?</span>)</a>
	    	<a href="{$path}/MyResearch/OverdriveWishList" data-role="button" rel="external">{translate text='Wish List'} (<span id="wishlistOverDrivePlaceholder">?</span>)</a>
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