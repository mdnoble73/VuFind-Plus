{strip}
	{* Search box *}
	{include file="Search/searchbox-home.tpl"}

	<div id="home-page-login" class="text-center row">
		<div class="logoutOptions hidden-phone" {if !$user} style="display: none;"{/if}>
			<a id="myAccountNameLink" href="{$path}/MyAccount/Home">Logged in as {$user->firstname|capitalize} {$user->lastname|capitalize}</a>
		</div>
		<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
			<a href="{$path}/MyAccount/Logout" id="logoutLink" >{translate text="Log Out"}</a>
		</div>
		<div class="loginOptions" {if $user} style="display: none;"{/if}>
			{if $showLoginButton == 1}
				<a href="{$path}/MyResearch/Home" class='loginLink' title='Login To My Account' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN TO MY ACCOUNT"}</a>
			{/if}
		</div>
	</div>

	<div id="xs-main-content-insertion-point" class="row"></div>

	{if $user}
		<div id="results-sort-label" class="row">
			{translate text='My Account'}
		</div>
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}