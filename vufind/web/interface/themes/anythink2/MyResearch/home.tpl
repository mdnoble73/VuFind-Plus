<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  {if $user->cat_username}
    <h1>{translate text='Account Overview'}</h1>
    {if $userNoticeFile}
      {include file=$userNoticeFile}
    {/if}
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
    <h4>Items Out - See and renew currently checked out items</h4>
    <ul>
      <li><a {if $pageTemplate=="checkedout.tpl"}class="active"{/if} href="{$path}/MyAccount/CheckedOut">{translate text='Books, Movies, and Music'}{if !empty($profile.numCheckedOut)} ({$profile.numCheckedOut}){/if}</a></li>
      <li><a {if $pageTemplate=="eContentCheckedOut.tpl"}class="active"{/if} href="{$path}/MyAccount/EContentCheckedOut">{translate text='eBooks and eAudio'} {if !empty($profile.numEContentCheckedOut)}({$profile.numEContentCheckedOut}){/if}</a></li>
      <li><a {if $pageTemplate=="overDriveCheckedOut.tpl"}class="active"{/if} href="{$path}/MyAccount/OverdriveCheckedOut">{translate text='OverDrive eBooks and eAudio'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a></li>
    </ul>
    <h4>Hold Requests - Items ready to be picked up</h4>
    <ul>
      <li><a {if $pageTemplate=="holds.tpl"}class="active"{/if} href="{$path}/MyAccount/Holds">{translate text='Titles to pickup at the library'}{if !empty($profile.numHoldsAvailable)} ({$profile.numHoldsAvailable}){/if}</a></li>
      {if $hasProtectedEContent}
        <li><a {if $pageTemplate=="eContentHolds.tpl"}class="active"{/if} href="{$path}/MyAccount/EContentHolds">{translate text='Titles to pickup online'} {if !empty($profile.numEContentAvailableHolds)}({$profile.numEContentAvailableHolds}){/if}</a></li>
      {/if}
      <li><a {if $pageTemplate=="overDriveHolds.tpl"}class="active"{/if} href="{$path}/MyAccount/OverdriveHolds">{translate text='Titles to pickup online from OverDrive'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a></li>
    </ul>
      
    <h4>Hold Requests - Items waiting to become available</h4>
    <ul>
      <li><a {if $pageTemplate=="holds.tpl"}class="active"{/if} href="{$path}/MyAccount/Holds">{translate text='Books, Movies, and Music'}{if !empty($profile.numHoldsRequested)} ({$profile.numHoldsRequested}){/if}</a></li>
      {if $hasProtectedEContent}
        <li><a {if $pageTemplate=="eContentHolds.tpl"}class="active"{/if} href="{$path}/MyAccount/EContentHolds">{translate text='eBooks and eAudio'} {if !empty($profile.numEContentUnavailableHolds)}({$profile.numEContentUnavailableHolds}){/if}</a></li>
      {/if}
      <li><a {if $pageTemplate=="overDriveHolds.tpl"}class="active"{/if} href="{$path}/MyAccount/OverdriveHolds">{translate text='OverDrive eBooks and eAudio'} (<span id="unavailableHoldsOverDrivePlaceholder">?</span>)</a></li>
    </ul>
    
    <h4>Lists - Things I have read and may want to read later</h4>
    <ul>
      <li><a {if $pageTemplate=="favorites.tpl"}class="active"{/if} href="{$path}/MyAccount/Favorites">{translate text='Favorites'}</a></li>
      <li><a {if $pageTemplate=="readingHistory.tpl"}class="active"{/if} href="{$path}/MyAccount/ReadingHistory">{translate text='Checkout History'}</a></li>
      {if $hasProtectedEContent}
        <li><a {if $pageTemplate=="eContentWishList.tpl"}class="active"{/if} href="{$path}/MyAccount/MyEContentWishlist">{translate text='eBooks and eAudio Wishlist'} {if !empty($profile.numEContentWishList)}({$profile.numEContentWishList}){/if}</a></li>
      {/if}
      <li><a {if $pageTemplate=="overDriveWishList.tpl"}class="active"{/if} href="{$path}/MyAccount/OverdriveWishList">{translate text='OverDrive Wish List'} (<span id="wishlistOverDrivePlaceholder">?</span>)</a></li>
      {if $enableMaterialsRequest}
      <li><a {if $pageTemplate=="myMaterialRequests.tpl"}class="active"{/if} href="{$path}/MaterialsRequest/MyRequests">{translate text='Requests'} {if !empty($profile.numMaterialsRequests)}({$profile.numMaterialsRequests}){/if}</a></li>
      {/if}
              {* Only highlight saved searches as active if user is logged in: *}
      <li><a {if $user && $pageTemplate=="history.tpl"}class="active"{/if} href="{$path}/Search/History?require_login">{translate text='Saved Searches'}</a></li>
    </ul>
    
    <h4>Profile - Update PIN, email and preferences</h4>
    <ul>
      <li><a {if $pageTemplate=="profile.tpl"}class="active"{/if} href="{$path}/MyAccount/Profile">{translate text='Profile'}</a></li>
      {if $showFines}
      <li><a {if $pageTemplate=="fines.tpl"}class="active"{/if} href="{$path}/MyAccount/Fines">{translate text='Fines and Messages'}</a></li>
      {/if}
    </ul>
    
    <script type="text/javascript">
      getOverDriveSummary();
    </script>
  {else}
      You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
  {/if}
</div>
