{strip}
	{* Search box *}
	{if !$horizontalSearchBar}
		{include file="Search/searchbox-home.tpl"}
	{/if}

	{include file="login-sidebar.tpl"}

	{* TODO: remove, probably won't be used anymore with the vertical menubar *}
	<div id="xs-main-content-insertion-point" class="row"></div>

	{if $user}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}