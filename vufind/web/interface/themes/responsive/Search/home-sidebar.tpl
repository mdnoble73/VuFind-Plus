{strip}
{* Search box *}
	{include file="Search/searchbox-home.tpl"}

	<div id="home-page-login" class="text-center row">
		<div class="logoutOptions hidden-phone" {if !$user} style="display: none;"{/if}>
			<a id="myAccountNameLink" href="{$path}/MyResearch/Home">Logged In As {$user->firstname|capitalize} {$user->lastname|capitalize}</a>
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

	<div id="xs-main-content-insertion-point" class="row"></div>

	{if $user}
		{* Account Menu *}
		{include file="MyResearch/menu.tpl"}
	{/if}


	<div id="home-page-library-section" class="row">
		<a href="{$path}/AJAX/JSON?method=getHoursAndLocations" data-title="Library Hours and Locations" class="modalDialogTrigger">
			<div id="home-page-hours-locations">
				LIBRARY HOURS & LOCATIONS
			</div>
		</a>

		{if $libraryLinks}
			<div id="home-library-links" class="sidebar-links">
				<div class="panel-group" id="link-accordion">
					{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
						<div class="panel {if $smarty.foreach.linkLoop.first && !$user}active{/if}">
							<a data-toggle="collapse" data-parent="#link-accordion" href="#{$categoryName|escapeCSS}Panel">
								<div class="panel-heading">
									<div class="panel-title">
										{$categoryName}
									</div>
								</div>
							</a>
							<div id="{$categoryName|escapeCSS}Panel" class="panel-collapse collapse {if $smarty.foreach.linkLoop.first && !$user}in{/if}">
								<div class="panel-body">
									{foreach from=$linkCategory item=linkUrl key=linkName}
										<div>
											<a href="{$linkUrl}">{$linkName}</a>
										</div>
									{/foreach}
								</div>
							</div>
						</div>
					{/foreach}

				</div>
			</div>
		{/if}
	</div>
{/strip}