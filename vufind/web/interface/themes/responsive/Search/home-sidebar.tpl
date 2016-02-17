{strip}
{* Search box *}
	{if !$horizontalSearchBar}
		{include file="Search/searchbox-home.tpl"}
	{/if}

	{include file="login-sidebar.tpl"}

	{if $user}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{if $showExploreMore}
		{include file="explore-more-sidebar.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}