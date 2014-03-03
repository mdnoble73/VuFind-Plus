{strip}

	<div class="col-xs-5 col-sm-4 col-md-4 col-lg-4">
		<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}">
			<img src="{if $tinyLogo}{$tinyLogo}{else}{img filename="logo_tiny.png"}{/if}" alt="{$librarySystemName}" title="Return to Catalog Home" id="header-logo"/>
		</a>
	</div>

	<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
		<div class="col-xs-4 col-sm-2 col-sm-offset-4 col-md-2 col-md-offset-4 col-lg-2 col-lg-offset-4">
			<div class="header-button header-primary">
				<a id="myAccountNameLink" href="{$path}/MyAccount/Home">
					{translate text="Your Account"}
				</a>
			</div>
		</div>
		<div class="col-xs-3 col-sm-2 col-md-2 col-lg-2">
			<div class="header-button header-primary" >
				<a href="{$path}/MyResearch/Logout" id="logoutLink" >{translate text="Log Out"}</a>
			</div>
		</div>
	</div>

	<div class="loginOptions col-xs-3 col-xs-offset-4 col-sm-2 col-sm-offset-6 col-md-2 col-md-offset-6 col-lg-2 col-lg-offset-6"{if $user} style="display: none;"{/if}>
		<div class="header-button header-primary">
			{if $showLoginButton == 1}
				<a id="headerLoginLink" href="{$path}/MyAccount/Home" class='loginLink' title='Login' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN"}</a>
			{/if}
		</div>
	</div>

{/strip}