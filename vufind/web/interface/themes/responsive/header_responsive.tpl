{strip}
<div class="navbar navbar-static-top">
	<div class="navbar-inner">
		<div>
			<a class="navbar-brand" href="{if $homeLink}{$homeLink}{else}{$path}/{/if}">
				<img class="brand" src="{if $tinyLogo}{$tinyLogo}{else}{img filename="logo_tiny.png"}{/if}" alt="{$librarySystemName}" title="Return to Catalog Home" id="header_logo"/>
			</a>
			<div class="pull-right">
				<ul class="nav navbar-nav">
					<li class="logoutOptions hidden-phone header-button header-primary" {if !$user} style="display: none;"{/if}>
						<a id="myAccountNameLink" href="{$path}/MyResearch/Home">{$user->firstname|capitalize} {$user->lastname|capitalize}</a>
					</li>
					<li class="logoutOptions header-button header-primary" {if !$user} style="display: none;"{/if}>
						<a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a>
					</li>
					<li class="logoutOptions header-button header-primary" {if !$user} style="display: none;"{/if}>
						<a href="{$path}/MyResearch/Logout" id="logoutLink" >{translate text="Log Out"}</a>
					</li>
					<li class="loginOptions header-button header-primary" {if $user} style="display: none;"{/if}>
						{if $showLoginButton == 1}
							<a id="headerLoginLink" href="{$path}/MyResearch/Home" class='loginLink' title='Login' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN"}</a>
						{/if}
					</li>
					{if is_array($allLangs) && count($allLangs) > 1}
						<li class="header-button">
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
						</li>
					{/if}
				</ul>
			</div>
		</div>

	</div>
</div>
{/strip}