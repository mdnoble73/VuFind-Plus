{strip}
<div id="sidebar">
	{include file="Record/view-title-details.tpl"}
	
	{include file="Record/view-tags.tpl"}

	{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 0}
		<div class="sidegroup" id="inProspectorSidegroup" style="display:none">
			{* Display in Prospector Sidebar *}
			<div id="inProspectorPlaceholder"></div>
		</div>
	{/if}
</div> {* End sidebar *}
{/strip}