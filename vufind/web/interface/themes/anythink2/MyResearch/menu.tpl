{if $user != false}
  <h2>{translate text='Your Account'}</h2>
  <div id="profileMessages">
    {if $profile.finesval > 0}
        <div class="alignright">
          <span title="Please Contact your local library to pay fines or Charges." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to pay fines or Charges.')">Your account has {$profile.fines} in fines.</span>
          {if $showEcommerceLink && $profile.finesval > $minimumFineAmount}
            <div><a href='{$ecommerceLink}'>Click to Pay Fines Online</a></div>
          {/if}
        </div>
    {/if}
    {if $profile.expireclose}<a class="alignright" title="Please contact your local library to have your library card renewed." style="color:green; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">Your library card will expire on {$profile.expires}.</a>{/if}
  </div>
  <ul>
    <li><a {if $pageTemplate=="favorites.tpl"}class="active"{/if} href="{$path}/MyResearch/Favorites">{translate text='Suggestions, Lists &amp; Tags'}</a></li>
    <li><a {if $pageTemplate=="readingHistory.tpl"}class="active"{/if} href="{$path}/MyResearch/ReadingHistory">{translate text='My Reading History'}</a></li>
    {if $showFines}
    <li><a {if $pageTemplate=="fines.tpl"}class="active"{/if} href="{$path}/MyResearch/Fines">{translate text='Fines and Messages'}</a></li>
    {/if}
    {if $enableMaterialsRequest}
    <li><a {if $pageTemplate=="myMaterialRequests.tpl"}class="active"{/if} href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} {if !empty($profile.numMaterialsRequests)}({$profile.numMaterialsRequests}){/if}</a></li>
    {/if}
    <li><a {if $pageTemplate=="profile.tpl"}class="active"{/if} href="{$path}/MyResearch/Profile">{translate text='Profile'}</a></li>
    {* Only highlight saved searches as active if user is logged in: *}
    <li><a {if $user && $pageTemplate=="history.tpl"}class="active"{/if} href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></li>
  </ul>
  <h4>Print Titles</h4>
  <ul>
    <li><a {if $pageTemplate=="checkedout.tpl"}class="active"{/if} href="{$path}/MyResearch/CheckedOut">{translate text='Checked Out Items'}{if !empty($profile.numCheckedOut)} ({$profile.numCheckedOut}){/if}</a></li>
    <li><a {if $pageTemplate=="holds.tpl"}class="active"{/if} href="{$path}/MyResearch/Holds">{translate text='Available Holds'}{if !empty($profile.numHoldsAvailable)} ({$profile.numHoldsAvailable}){/if}</a></li>
    <li><a {if $pageTemplate=="holds.tpl"}class="active"{/if} href="{$path}/MyResearch/Holds">{translate text='Unavailable Holds'}{if !empty($profile.numHoldsRequested)} ({$profile.numHoldsRequested}){/if}</a></li>
  </ul>
  <h4>eContent Titles</h4>
  <ul>
    <li><a {if $pageTemplate=="eContentCheckedOut.tpl"}class="active"{/if} href="{$path}/MyResearch/EContentCheckedOut">{translate text='Checked Out Items'} {if !empty($profile.numEContentCheckedOut)}({$profile.numEContentCheckedOut}){/if}</a></li>
    {if $hasProtectedEContent}
      <li><a {if $pageTemplate=="eContentHolds.tpl"}class="active"{/if} href="{$path}/MyResearch/EContentHolds">{translate text='Available Holds'} {if !empty($profile.numEContentAvailableHolds)}({$profile.numEContentAvailableHolds}){/if}</a></li>
      <li><a {if $pageTemplate=="eContentHolds.tpl"}class="active"{/if} href="{$path}/MyResearch/EContentHolds">{translate text='Unavailable Holds'} {if !empty($profile.numEContentUnavailableHolds)}({$profile.numEContentUnavailableHolds}){/if}</a></li>
      <li><a {if $pageTemplate=="eContentWishList.tpl"}class="active"{/if} href="{$path}/MyResearch/MyEContentWishlist">{translate text='Wish List'} {if !empty($profile.numEContentWishList)}({$profile.numEContentWishList}){/if}</a></li>
    {/if}
  </ul>
  <h4>OverDrive Titles</h4>
  <ul>
    <li><a {if $pageTemplate=="overDriveCheckedOut.tpl"}class="active"{/if} href="{$path}/MyResearch/OverdriveCheckedOut">{translate text='Checked Out Items'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a></li>
    <li><a {if $pageTemplate=="overDriveHolds.tpl"}class="active"{/if} href="{$path}/MyResearch/OverdriveHolds">{translate text='Available Holds'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a></li>
    <li><a {if $pageTemplate=="overDriveHolds.tpl"}class="active"{/if} href="{$path}/MyResearch/OverdriveHolds">{translate text='Unavailable Holds'} (<span id="unavailableHoldsOverDrivePlaceholder">?</span>)</a></li>
    <li><a {if $pageTemplate=="overDriveWishList.tpl"}class="active"{/if} href="{$path}/MyResearch/OverdriveWishList">{translate text='Wish List'} (<span id="wishlistOverDrivePlaceholder">?</span>)</a></li>
  </ul>
{/if}
<script type="text/javascript">
  getOverDriveSummary();
</script>
