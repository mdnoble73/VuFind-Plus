{strip}
	<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2">
		<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}">
			<img src="{if $tinyLogo}{$tinyLogo}{else}{img filename="logo_tiny.png"}{/if}" alt="{$librarySystemName}" title="Return to Catalog Home" id="header-logo"/>
		</a>
	</div>

	<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
		<div class="col-xs-2 col-sm-2 col-sm-offset-1 col-md-2 col-md-offset-2 col-lg-2 col-lg-offset-2">
			<div class="header-button header-primary">
				<a id="myAccountNameLink" href="{$path}/MyResearch/Home">
					{$user->firstname|capitalize} {$user->lastname|capitalize}
				</a>
			</div>
		</div>
		<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">
			<div class="header-button header-primary">
				<a id="myAccountNameLink" href="{$path}/MyResearch/Home">
					{translate text="Your Account"}
				</a>
			</div>
		</div>
		<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">
			<div class="header-button header-primary" >
				<a href="{$path}/MyResearch/Logout" id="logoutLink" >{translate text="Log Out"}</a>
			</div>
		</div>
	</div>

	<div class="loginOptions col-xs-3 col-xs-offset-5 col-sm-2 col-sm-offset-6 col-md-2 col-md-offset-7 col-lg-2 col-lg-offset-8"{if $user} style="display: none;"{/if}>
		<div class="header-button header-primary">
			{if $showLoginButton == 1}
				<a id="headerLoginLink" href="{$path}/MyResearch/Home" class='loginLink' title='Login' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN"}</a>
			{/if}
		</div>
	</div>

{/strip}