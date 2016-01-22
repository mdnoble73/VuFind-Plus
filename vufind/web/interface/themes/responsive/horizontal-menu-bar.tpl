{strip}
	{if $user}{* Logged In *}
		<a href="{$path}/MyAccount/Logout" id="logoutLink" class="menu-icon" title="{translate text="Log Out"}">
			<img src="{img filename='/interface/themes/responsive/images/Logout.png'}" alt="{translate text="Log Out"}">
		</a>
		<a href="#account-menu" onclick="VuFind.Menu.showAccount(this)" class="menu-icon" title="Account">
			<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Account">
		</a>
	{else} {* Not Logged In *}
		<a href="{$path}/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">
			<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="{translate text='Login'}">
		</a>
	{/if}
	<a href="#" onclick="VuFind.Menu.showMenu(this)" class="menu-icon" title="Menu">
		<img src="{img filename='/interface/themes/responsive/images/Menu.png'}" alt="Menu">
	</a>

	<a href="#" onclick="VuFind.Menu.showSearch(this)" class="menu-icon menu-left" title="Search">
		<img src="{img filename='/interface/themes/responsive/images/Search.png'}" alt="Search">
	</a>

	{if $showExploreMore}
		<a href="#" onclick="VuFind.Menu.showExploreMore(this)" class="menu-icon menu-left" title="{translate text='Explore More'}">
			<img src="{img filename='/interface/themes/responsive/images/ExploreMore.png'}" alt="{translate text='Explore More'}">
		</a>
	{/if}
{/strip}