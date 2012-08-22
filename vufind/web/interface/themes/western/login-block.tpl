<div id="loginBlock">
	<div id="logoutOptions"{if !$user} style="display: none;"{/if}>
		<a href="{$path}/MyResearch/Home" id="myAccountNameLink">{$user->firstname|capitalize} {$user->lastname|capitalize}</a> | <a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a> |
		<a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a>
	</div>
	<div id="loginOptions"{if $user} style="display: none;"{/if}>
		{if $authMethod == 'Shibboleth'}
			<a href="{$sessionInitiator}">{translate text="Institutional Login"}</a>
		{elseif $showLoginButton == 1}
			<a href="{$path}/MyResearch/Home" class='loginLink'>{translate text="Login to View Your Account, Renew Books, and more."}</a>
		{/if}
	</div>
</div>
<div class="clearer"></div>