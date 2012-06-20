<div class="sidegroup">
{if $user != false}
  <h4>{translate text='Your Account'}</h4>
  <div class="sidegroupContents">
    <div id="profileMessages">
      {if $profile.finesval > 0}
          <div class ="alignright">
          <span title="Please Contact your local library to pay fines or Charges." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to pay fines or Charges.')">Your account has {$profile.fines} in fines.</span>
          </div>
      {/if}
      
      {if $profile.expireclose}<a class ="alignright" title="Please contact your local library to have your library card renewed." style="color:green; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">Your library card will expire on {$profile.expires}.</a>{/if}
    </div>
  
  <div id="myAccountLinks">
    <div class="myAccountLink{if $pageTemplate=="fines.tpl"} active{/if}" title="Fines and account messages"><a href="{$path}/MyResearch/Fines">{translate text='Fines and Messages'}</a></div>
    <div class="myAccountLink">Print Titles
      <div class="myAccountLink{if $pageTemplate=="checkedout.tpl"} active{/if}"><a href="{$path}/MyResearch/CheckedOut">{translate text='Checked Out Items'}{if $profile.numCheckedOut} ({$profile.numCheckedOut}){/if}</a></div>
      <div class="myAccountLink{if $pageTemplate=="holds.tpl"} active{/if}"><a href="{$path}/MyResearch/Holds">{translate text='Available Holds'}{if $profile.numHoldsAvailable} ({$profile.numHoldsAvailable}){/if}</a></div>
      <div class="myAccountLink{if $pageTemplate=="holds.tpl"} active{/if}"><a href="{$path}/MyResearch/Holds">{translate text='Requested'}{if $profile.numHoldsRequested} ({$profile.numHoldsRequested}){/if}</a></div>
    </div>
    {if $hasProtectedEContent}
    <div class="myAccountLink">eContent Titles
    	<div class="myAccountLink{if $pageTemplate=="eContentCheckedOut.tpl"} active{/if}"><a href="{$path}/MyResearch/EContentCheckedOut">{translate text='Checked Out Items'} ({$profile.numEContentCheckedOut})</a></div>
    	<div class="myAccountLink{if $pageTemplate=="eContentHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/EContentHolds">{translate text='Available For Pickup'} ({$profile.numEContentAvailableHolds})</a></div>
    	<div class="myAccountLink{if $pageTemplate=="eContentHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/EContentHolds">{translate text='Requested'} ({$profile.numEContentUnavailableHolds})</a></div>
    	<div class="myAccountLink{if $pageTemplate=="eContentWishList.tpl"} active{/if}"><a href="{$path}/MyResearch/MyEContentWishlist">{translate text='Wish List'} ({$profile.numEContentWishList})</a></div>
    </div>
    {/if}
    <div class="myAccountLink">OverDrive Titles
    	<div class="myAccountLink{if $pageTemplate=="overDriveCheckedOut.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveCheckedOut">{translate text='Checked Out Items'} (<span id="checkedOutItemsOverDrivePlaceholder">?</span>)</a></div>
    	<div class="myAccountLink{if $pageTemplate=="overDriveHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveHolds">{translate text='Available Holds'} (<span id="availableHoldsOverDrivePlaceholder">?</span>)</a></div>
    	<div class="myAccountLink{if $pageTemplate=="overDriveHolds.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveHolds">{translate text='Unavailable Holds'} (<span id="unavailableHoldsOverDrivePlaceholder">?</span>)</a></div>
    	<div class="myAccountLink{if $pageTemplate=="overDriveWishList.tpl"} active{/if}"><a href="{$path}/MyResearch/OverdriveWishList">{translate text='Wish List'} (<span id="wishlistOverDrivePlaceholder">?</span>)</a></div>
    </div>
    <div class="myAccountLink{if $pageTemplate=="list.tpl"} active{/if}"><a href="{$path}/MyResearch/MyLists">{translate text='My Lists'}</a></div>
    <div class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyResearch/Profile">{translate text='Profile'}</a></div>
    {if $enableMaterialsRequest}
      <div class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="Materials Requests"><a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} ({$profile.numMaterialsRequests})</a></div>
    {/if}
    {* Only highlight saved searches as active if user is logged in: *}
    <div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
    </div>
  </div>
{/if}
</div>
