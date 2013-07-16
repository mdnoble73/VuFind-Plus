{strip}
<div data-role="dialog">
	<div data-role="header" data-theme="d" data-position="inline">
		<h1>{$popupTitle|translate}</h1>
	</div>
	<div data-role="content">
		{if $popupContent}
			{$popupContent}
		{else}
			{include file="$popupTemplate"}
		{/if}
	</div>
</div>
{/strip}