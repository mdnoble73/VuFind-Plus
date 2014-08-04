{strip}
	{* Search box *}
	{include file="Search/searchbox-home.tpl"}

	{include file="login-sidebar.tpl"}

	<div id="xs-main-content-insertion-point" class="row"></div>

	{if $user}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}