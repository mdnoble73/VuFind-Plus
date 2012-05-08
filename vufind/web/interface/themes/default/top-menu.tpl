<div id="menu-header">
	<div id="menu-header-links">
		<div id="menu-account-links">
		<span id="myAccountNameLink" class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/Home">{if strlen($user->displayName) > 0}{$user->displayName}{else}{$user->firstname|capitalize} {$user->lastname|capitalize}{/if}</a></span>
		<span class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/Home">{translate text="My Account"}</a></span>
		<span class="menu-account-link logoutOptions top-menu-item"{if !$user} style="display: none;"{/if}><a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a></span>
		{if $showLoginButton == 1}
		  <span class="menu-account-link loginOptions top-menu-item" {if $user} style="display: none;"{/if}><a href="{$path}/MyResearch/Home" class='loginLink'>{translate text="My Account"}</a></span>
		{/if}
		</div>
	</div>
</div>