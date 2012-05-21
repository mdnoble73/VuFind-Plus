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
		<span class="top-menu-item"><a title="Contact Us" href="http://answers.douglascountylibraries.org/">Ask Us</a></span>
		<span class="top-menu-item"><a href="https://epayments.douglascountylibraries.org/eCommerceWebModule/Home">Pay Fines</a></span>
		<span class="top-menu-item"><a title="Literacy" href="http://douglascountylibraries.org/AboutUs/Literacy">Literacy</a></span>
		<span class="top-menu-item"><a title="Small Business Help" href="http://douglascountylibraries.org/Research/iGuides/SmallBusiness">BizInfo</a></span>
		<span class="top-menu-item"><a title="Douglas County History Research Center" href="http://history.douglascountylibraries.org">DCHRC</a></span>
		<span class="top-menu-item"><a title="Kids Corner" href="http://douglascountylibraries.org/kids">Kids</a></span>
		<span class="top-menu-item"><a href="http://douglascountylibraries.org/storytime">Storytime</a></span>
		<span class="top-menu-item"><a title="Teen Scene" href="http://teens.douglascountylibraries.org">Teens</a></span>
	</div>
</div>