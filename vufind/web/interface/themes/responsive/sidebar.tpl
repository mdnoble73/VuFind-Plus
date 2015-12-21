{strip}
	<div class="row" id="vertical-menu-bar-container">
		<div class="hidden-xs col-sm-1 col-md-1 col-lg-1" id="vertical-menu-bar-wrapper">
			<div id="vertical-menu-bar">
				<div class="menu-bar-option">
					<a href="#" onclick="VuFind.Menu.showSearch(this)" class="menu-icon" title="Search">
						<img src="{img filename='/interface/themes/responsive/images/Search.png'}" alt="Search">
						<div class="menu-bar-label">Search</div>
					</a>
				</div>
				{if $user}{* Logged In *}
					{*<a href="{$path}/MyAccount/Logout" id="logoutLink" class="menu-icon" title="{translate text="Log Out"}">
						<img src="{img filename='/interface/themes/responsive/images/Logout.png'}" alt="{translate text="Log Out"}">
						<div class="menu-bar-label">{translate text="Log Out"}</div>
					</a>*}
					<div class="menu-bar-option">
						<a href="#" onclick="VuFind.Menu.showAccount(this)" class="menu-icon" title="Account">
							<img src="{img filename='/interface/themes/responsive/images/Account.png'}" alt="Account">
							<div class="menu-bar-label">Account</div>
						</a>
					</div>
				{else} {* Not Logged In *}
					<div class="menu-bar-option">
						<a href="{$path}/MyAccount/Home" id="loginLink" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" data-login="true" class="menu-icon" title="{translate text='Login'}">
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

				<script type="text/javascript">
					$(function(){ldelim}
						{if $module == "Search"}
							$('.menu-bar-option:nth-child(1)>a', '#vertical-menu-bar').filter(':visible').click();
						{elseif $module == "MyAccount" || $module == "Admin"}
							$('.menu-bar-option:nth-child(2)>a', '#vertical-menu-bar').filter(':visible').click();
						{else}
							$('.menu-bar-option:nth-child(3)>a', '#vertical-menu-bar').filter(':visible').click();
						{/if}
					{rdelim})
				</script>
			</div>
		</div>

		<div class="col-xs-12 col-sm-10 col-md-10 col-lg-10" id="sidebar-content">
			{include file="$sidebar"}
		</div>
	</div>
{/strip}