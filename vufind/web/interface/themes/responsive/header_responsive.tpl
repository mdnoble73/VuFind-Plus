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

	<div class="loginOptions col-xs-3 col-xs-offset-3 col-sm-2 col-sm-offset-4 col-md-2 col-md-offset-6 col-lg-2 col-lg-offset-7"{if $user} style="display: none;"{/if}>
		<div class="header-button header-primary">
			{if $showLoginButton == 1}
				<a id="headerLoginLink" href="{$path}/MyResearch/Home" class='loginLink' title='Login' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN"}</a>
			{/if}
		</div>
	</div>

	<div class="col-xs-2 col-sm-2 col-md-1 col-lg-1" id="language-selector">
		{if is_array($allLangs) && count($allLangs) > 1}
			<div class="header-button">
				<a href="#" id="language_toggle" class="dropdown-toggle" data-toggle="dropdown">
					{foreach from=$allLangs key=langCode item=langName}
						{if $userLang == $langCode}
							{translate text=$langName} <span class="caret"></span>
						{/if}
					{/foreach}
				</a>
				<ul class="dropdown-menu">
					{foreach from=$allLangs key=langCode item=langName}
						<li><a id="lang{$langCode}" class='languageLink {if $userLang == $langCode} selected{/if}' href="{$fullPath}{if $requestHasParams}&amp;{else}?{/if}mylang={$langCode}">{translate text=$langName}</a></li>
					{/foreach}
				</ul>
			</div>
		{/if}
	</div>
{/strip}