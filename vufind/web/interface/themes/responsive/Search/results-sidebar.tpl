{strip}
	{* New Search Box *}
	{include file="Search/searchbox-home.tpl"}

	{* Narrow Results *}
	{if $sideRecommendations}
		{foreach from=$sideRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}
{/strip}