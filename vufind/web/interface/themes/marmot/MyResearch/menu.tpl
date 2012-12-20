{strip}
<div class="sidegroup">
{if $user != false}
	<h4>{translate text='Your Account'}</h4>
	<div id="profileMessages">
		{if $profile.finesval > 0}
				<div class ="alignright">
				
				{if $showEcommerceLink && $profile.finesval > $minimumFineAmount}
				<span style="color:red; font-weight:bold;">Your account has {$profile.fines} in fines.</span>
				<a href='{$ecommerceLink}' ><br/>Click to Pay Fines</a>
				{else}
					<span title="Please Contact your local library to pay fines or Charges." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to pay fines or Charges.')">Your account has {$profile.fines} in fines.</span>
				{/if}
				</div>
		{/if}
		
		{if $profile.expireclose}<a class ="alignright" title="Please contact your local library to have your library card renewed." style="color:green; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">Your library card will expire on {$profile.expires}.</a>{/if}
	</div>

	<div id="myAccountLinks">
		<div class="myAccountLink">{translate text="Checked Out"}
			<div class="myAccountLink{if $pageTemplate=="checkedout.tpl"} active{/if}"><a href="{$path}/MyResearch/CheckedOut" id="checkedOutPrint">{translate text='Books, Movies &amp; Music'} ({$profile.numCheckedOut})</a></div>
			<div class="myAccountLink{if $pageTemplate=="eContentCheckedOut.tpl"} active{/if}"><a href="{$path}/MyResearch/EContentCheckedOut" id="checkedOutEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentCheckedOut})</a></div>
			<div class="myAccountLink{if $pageTemplate=="overDriveCheckedOut.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveCheckedOut" id="checkedOutOverDrive">{translate text='OverDrive'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a></div>
		</div>
		<div class="myAccountLink">{translate text="Ready For Pickup"}
			<div class="myAccountLink{if $pageTemplate=="availableHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/Holds?section=available" id="availablePrint">{translate text='Books, Movies &amp; Music'} ({$profile.numHoldsAvailable})</a></div>
			{if $hasProtectedEContent}
			<div class="myAccountLink{if $pageTemplate=="eContentAvailableHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/EContentHolds?section=available" id="availableEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentAvailableHolds})</a></div>
			{/if}
			<div class="myAccountLink{if $pageTemplate=="overDriveAvailableHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveHolds?section=available" id="availableOverDrive">{translate text='OverDrive'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a></div>
		</div>
		<div class="myAccountLink">{translate text="On Hold"}
			<div class="myAccountLink{if $pageTemplate=="unavailableHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/Holds?section=unavailable" id="unavailablePrint">{translate text='Books, Movies &amp; Music'} ({$profile.numHoldsRequested})</a></div>
			{if $hasProtectedEContent}
			<div class="myAccountLink{if $pageTemplate=="eContentUnavailableHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/EContentHolds?section=unavailable" id="unavailableEContent">{translate text='eBooks and eAudio'} ({$profile.numEContentUnavailableHolds})</a></div>
			{/if}
			<div class="myAccountLink{if $pageTemplate=="overDriveUnavailableHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveHolds?section=unavailable" id="unavailableOverDrive">{translate text='OverDrive'} (<span id="unavailableHoldsOverDrivePlaceholder">?</span>)</a></div>
		</div>
		<div class="myAccountLink">{translate text="Wish Lists"}
			{if $hasProtectedEContent}
			<div class="myAccountLink{if $pageTemplate=="eContentWishList.tpl"} active{/if}"><a href="{$path}/MyResearch/MyEContentWishlist">{translate text='eBooks and eAudio'} ({$profile.numEContentWishList})</a></div>
			{/if}
			<div class="myAccountLink{if $pageTemplate=="overDriveWishList.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveWishList">{translate text='OverDrive'} (<span id="wishlistOverDrivePlaceholder">?</span>)</a></div>
		</div>
		<div class="myAccountLink{if $pageTemplate=="favorites.tpl"} active{/if}"><a href="{$path}/MyResearch/Favorites">{translate text='Suggestions, Lists &amp; Tags'}</a></div>
		{if $showFines}
		<div class="myAccountLink{if $pageTemplate=="fines.tpl"} active{/if}" title="Fines and account messages"><a href="{$path}/MyResearch/Fines">{translate text='Fines and Messages'}</a></div>
		{/if}
		{if $enableMaterialsRequest}
		<div class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="Materials Requests"><a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} ({$profile.numMaterialsRequests})</a></div>
		{/if}
		<div class="myAccountLink{if $pageTemplate=="readingHistory.tpl"} active{/if}"><a href="{$path}/MyResearch/ReadingHistory">{translate text='Reading History'}</a></div>
		<div class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyResearch/Profile">{translate text='Profile'}</a></div>
		{* Only highlight saved searches as active if user is logged in: *}
		<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
	</div>
{/if}
</div>
{/strip}
<script type="text/javascript">
getOverDriveSummary();
</script>