{strip}
	{if $user}{* Logged In *}
		<a href="{$path}/MyAccount/Logout" id="logoutLink" class="menu-icon" title="{translate text="Log Out"}">
			<img src="{img filename='/interface/themes/responsive/images/Logout.png'}" alt="{translate text="Log Out"}">
		</a>
		<a href="#account-menu" onclick="VuFind.Menu.showAccount()" class="menu-icon" title="Account">
			<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Account">
		</a>
	{else} {* Not Logged In *}
		<a href="{$path}/MyAccount/Home" id="loginLink" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" data-login="true" class="menu-icon" title="{translate text='Login'}">
			<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Login">
		</a>
	{/if}
	<a href="#" onclick="VuFind.Menu.showMenu()" class="menu-icon" title="Menu">
		<img src="{img filename='/interface/themes/responsive/images/Menu.png'}" alt="Menu">
	</a>

	<a href="#" onclick="VuFind.Menu.showSearch()" class="menu-icon menu-left" title="Search">
		<img src="{img filename='/interface/themes/responsive/images/Search.png'}" alt="Search">
	</a>

{/strip}