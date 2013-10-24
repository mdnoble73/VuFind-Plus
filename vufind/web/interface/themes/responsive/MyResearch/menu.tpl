{strip}
{if $user != false}
	<div class="sidegroup well">
		<h4>{translate text='Your Account'}</h4>
		{if $profile.finesval > 0 || $profile.expireclose}
			<div id="myAccountFines">
				{if $profile.finesval > 0}
					{if $showEcommerceLink && $profile.finesval > $minimumFineAmount}
						<div class="myAccountLink" style="color:red; font-weight:bold;">Your account has {$profile.fines} in fines.</div>
						<div class="myAccountLink"><a href='{$ecommerceLink}' >{if $payFinesLinkText}{$payFinesLinkText}{else}Click to Pay Fines Online{/if}</a></div>
					{else}
						<div class="myAccountLink" title="Please Contact your local library to pay fines or Charges." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to pay fines or Charges.')">Your account has {$profile.fines} in fines.</div>
					{/if}
				{/if}

				{if $profile.expireclose}<div class="myAccountLink"><a class ="alignright" title="Please contact your local library to have your library card renewed." style="color:green; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">Your library card will expire on {$profile.expires}.</a></div>{/if}
			</div>
			<br/>
		{/if}

		<div id="myAccountLinks">
			<ul>
			<li class="myAccountLink{if $pageTemplate=="checkedout.tpl"} active{/if}"><a href="{$path}/MyAccount/CheckedOut" id="checkedOut">{translate text='Checked Out'} ({$profile.numCheckedOutTotal})</a></li>
			<li class="myAccountLink{if $pageTemplate=="holds.tpl"} active{/if}"><a href="{$path}/MyAccount/Holds" id="holds">{translate text='On Hold'} ({$profile.numHoldsTotal})</a></li>

			{if $showFines}
			<li class="myAccountLink{if $pageTemplate=="fines.tpl"} active{/if}" title="Fines and account messages"><a href="{$path}/MyResearch/Fines">{translate text='Fines and Messages'}</a></li>
			{/if}
			{if $enableMaterialsRequest}
			<li class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="Materials Requests"><a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} ({$profile.numMaterialsRequests})</a></li>
			{/if}
			<li class="myAccountLink{if $pageTemplate=="readingHistory.tpl"} active{/if}"><a href="{$path}/MyResearch/ReadingHistory">{translate text='Reading History'}</a></li>
			<li class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyResearch/Profile">{translate text='Profile'}</a></li>
			{* Only highlight saved searches as active if user is logged in: *}
			<li class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></li>
		</div>
	</div>

	<div class="sidegroup well">
		<h4>{translate text='My Lists'}</h4>
		<div class="myAccountLink{if $pageTemplate=="myRatings.tpl"} active{/if}"><a href="{$path}/MyResearch/MyRatings">{translate text='Titles You Rated'}</a></div>
		{foreach from=$lists item=list}
			{if $list.id != -1}
				<div class="myAccountLink"><a href="{$list.url}">{$list.name}</a></div>
			{/if}
		{/foreach}
	</div>

	{if $tagList}
		<div class="sidegroup well">
			<h4>{translate text='My Tags'}</h4>
			<ul>
			{foreach from=$tagList item=tag}
				<li class="myAccountLink">
					<a href='{$path}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;basicType=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt})
					<a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from all titles?");'>
						<span class="silk tag_blue_delete" title="Delete Tag">&nbsp;</span>
					</a>
				</li>
			{/foreach}
			</ul>
		</div>
	{/if}
{/if}
{/strip}
