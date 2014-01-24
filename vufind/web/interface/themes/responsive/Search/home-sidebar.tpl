{strip}
{* Search box *}
	{include file="Search/searchbox-home.tpl"}

	<div id="home-page-login" class="text-center">
		<div class="logoutOptions hidden-phone" {if !$user} style="display: none;"{/if}>
			<a id="myAccountNameLink" href="{$path}/MyResearch/Home">Logged In As {$user->firstname|capitalize} {$user->lastname|capitalize}</a>
		</div>
		<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
			<a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a>
		</div>
		<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
			<a href="{$path}/MyResearch/Logout" id="logoutLink" >{translate text="Log Out"}</a>
		</div>
		<div class="loginOptions" {if $user} style="display: none;"{/if}>
			{if $showLoginButton == 1}
				<a href="{$path}/MyResearch/Home" class='loginLink' title='Login To My Account' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN TO MY ACCOUNT"}</a>
			{/if}
		</div>
	</div>
	<div id="home-page-library-section">
		<a href="alert('TODO: Show a list of locations and hours when clicking here')">
			<div id="home-page-hours-locations">
				LIBRARY HOURS & LOCATIONS
			</div>
		</a>
		<div id="home-library-links">
			<div class="panel-group" id="link-accordion">
				{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
					<div class="panel {if $smarty.foreach.linkLoop.first}active{/if}">
						<a data-toggle="collapse" data-parent="#link-accordion" href="#{$categoryName|escapeCSS}Panel">
							<div class="panel-heading">
								<div class="panel-title">
									{$categoryName}
								</div>
							</div>
						</a>
						<div id="{$categoryName|escapeCSS}Panel" class="panel-collapse collapse {if $smarty.foreach.linkLoop.first}in{/if}">
							<div class="panel-body">
								{foreach from=$linkCategory item=linkUrl key=linkName}
									<div class="col-sm-5 col-md-5 col-lg-5">
										<a href="{$linkUrl}">{$linkName}</a>
									</div>
								{/foreach}
							</div>
						</div>
					</div>
				{/foreach}

			</div>
		</div>
	</div>
{/strip}