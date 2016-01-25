{strip}
	{if $displaySidebarMenu}
	<div class="hidden-xs col-sm-1 col-md-1 col-lg-1" id="vertical-menu-bar-wrapper">
	<div id="vertical-menu-bar">
	<div class="menu-bar-option">
		<a href="#" onclick="VuFind.Menu.showSearch(this)" class="menu-icon" title="Search">
			<img src="{img filename='/interface/themes/responsive/images/Search.png'}" alt="Search">
			<div class="menu-bar-label">Search</div>
		</a>
	</div>
	{if $user}{* Logged In *}
		<div class="menu-bar-option">
			<a href="#" onclick="VuFind.Menu.showAccount(this)" class="menu-icon" title="Account">
				<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Account">
				<div class="menu-bar-label">Account</div>
			</a>
		</div>
	{else} {* Not Logged In *}
		<div class="menu-bar-option">
			<a href="{$path}/MyAccount/Home" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">
			{*<a href="{$path}{$fullPath}" id="loginLink" onclick="{if $isLoginPage}$('#username').focus();return false{else}return VuFind.Account.followLinkIfLoggedIn(this){/if}" data-login="true" class="menu-icon" title="{translate text='Login'}">*}
				<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="{translate text='Login'}">
				<div class="menu-bar-label">Account</div>
			</a>
		</div>
	{/if}
	<div class="menu-bar-option">
		<a href="#" onclick="VuFind.Menu.showMenu(this)" class="menu-icon" title="Menu">
			<img src="{img filename='/interface/themes/responsive/images/Menu.png'}" alt="Menu">
			<div class="menu-bar-label">Menu</div>
		</a>
	</div>
	{if $showExploreMore}
		<div class="menu-bar-option">
			<a href="#" onclick="VuFind.Menu.showExploreMore(this)" class="menu-icon" title="{translate text='Explore More'}">
				<img src="{img filename='/interface/themes/responsive/images/ExploreMore.png'}" alt="{translate text='Explore More'}">
				<div class="menu-bar-label">{translate text='Explore More'}</div>
			</a>
		</div>
	{/if}

		{* Open Appropriate Section on Initial Page Load *}
		<script type="text/javascript">
			$(function(){ldelim}
				{* Only trigger event if the side bar is visible *}
				{if $module == "Search" || $module == "Series" || $module == "Author" || $module == "Genealogy" || ($module == 'MyAccount' && $action == 'MyList' && !$listEditAllowed)}
					{* Treat Public Lists not owned by user as a Search Page rather than an MyAccount Page *}
				$('.menu-bar-option:nth-child(1)>a', '#vertical-menu-bar').filter(':visible').click();
				{elseif $module == "MyAccount" || $module == "Admin" || $module == "Circa" || $module == "EditorialReview" || $module == "Report"}
				$('.menu-bar-option:nth-child(2)>a', '#vertical-menu-bar').filter(':visible').click();
				{elseif $module == "Archive"}
				$('.menu-bar-option:nth-child(4)>a', '#vertical-menu-bar').filter(':visible').click();
				{else}
				$('.menu-bar-option:nth-child(3)>a', '#vertical-menu-bar').filter(':visible').click();
				{/if}
				{rdelim})
		</script>
	</div>
	</div>
	{/if}
{/strip}