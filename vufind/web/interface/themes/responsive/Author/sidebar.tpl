{strip}
	{* New Search Box *}
	{include file="Search/searchbox-home.tpl"}

	<div id="home-page-login" class="text-center row">
		<div class="logoutOptions hidden-phone" {if !$user} style="display: none;"{/if}>
			<a id="myAccountNameLink" href="{$path}/MyAccount/Home">Logged In As {$user->displayName|capitalize}</a>
		</div>
		<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
			<a href="{$path}/MyAccount/Logout" id="logoutLink" >{translate text="Log Out"}</a>
		</div>
		<div class="loginOptions" {if $user} style="display: none;"{/if}>
			{if $showLoginButton == 1}
				<a href="{$path}/MyAccount/Home" class='loginLink' title='Login To My Account' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN TO MY ACCOUNT"}</a>
			{/if}
		</div>
	</div>

	{* Sort the results*}
	{if $recordCount}
		<div id="results-sort-label" class="row">
			<label for="results-sort">{translate text='Sort Results By'}</label>
		</div>

		<div class="row">
			<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
				{foreach from=$sortList item=sortData key=sortLabel}
					<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
				{/foreach}
			</select>
		</div>
	{/if}

	<div id="xs-main-content-insertion-point" class="row"></div>

	{if $enrichment.novelist->similarAuthorCount != 0}
		<div id="similar-authors" class="sidebar-links row">
			<div class="panel">
				<div id="similar-authors-label" class="sidebar-label">
					{translate text="Similar Authors"}
				</div>
				<div class="similar-authors panel-body">
					{foreach from=$enrichment.novelist->authors item=similar}
						<div class="facetValue">
							<a href="{$similar.link}">{$similar.name}</a>
						</div>
					{/foreach}
				</div>
			</div>
		</div>
	{/if}

	{* Narrow Results *}
	{if $sideRecommendations}
		<div class="row">
			{foreach from=$sideRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		</div>
	{/if}

	{if $user}
		<div id="results-sort-label" class="row">
			{translate text='My Account'}
		</div>
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}
{/strip}