{strip}
<div id="popupboxHeader" class="header">
	{$popupTitle|translate}
	<a href='#' onclick='hideLightbox();return false;' id='popup_close_link'>{translate text="close"}</a>
</div>
<div id="popupboxContent" class="content">
	<div id='purchaseOptions'>
		{if $popupContent}
			{$popupContent}
		{else}
			{include file="$popupTemplate"}
		{/if}
</div>
{/strip}