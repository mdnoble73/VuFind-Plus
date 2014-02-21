{strip}
<div id="sidebar">
	<div id="recordTools">
		{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId summId=$id summTitle=$title recordUrl=$recordUrl}
	</div>
	
	{include file="Record/tag_sidegroup.tpl"}

	{if $enablePospectorIntegration == 1 && $showProspectorTitlesAsTab == 0}
		<div class="sidegroup" id="inProspectorSidegroup" style="display:none">
			{* Display in Prospector Sidebar *}
			<div id="inProspectorPlaceholder"></div>
		</div>
	{/if}
	
	{if $linkToAmazon == 1 && $isbn}
		<div class="titledetails">
			<a href="http://amazon.com/dp/{$isbn|@formatISBN}" class='amazonLink'> {translate text = "View on Amazon"}</a>
		</div>
	{/if}
</div> {* End sidebar *}
{/strip}