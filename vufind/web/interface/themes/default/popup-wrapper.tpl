{strip}
<div id="popupboxHeader" class="header">
	<span id="popupTitle">{$popupTitle|translate}</span>
	<a href='#' onclick='hideLightbox();return false;' id='popup_close_link'>{translate text="close"}</a>
</div>
<div id="popupboxContent" class="content">
	{if $popupContent}
		{$popupContent}
	{else}
		{include file="$popupTemplate"}
	{/if}
</div>
{/strip}