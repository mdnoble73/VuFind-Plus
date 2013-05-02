{strip}
<div id="sidebar">
	{include file="Record/view-title-details.tpl"}
	
	{include file="Record/tag_sidegroup.tpl"}

	{*
	<div class="sidegroup" id="similarTitlesSidegroup">
		<div id="similarTitlePlaceholder"></div>
		{if is_array($similarRecords) && count($similarRecords) > 0}
			<div id="relatedTitles">
				<h4>{translate text="Other Titles"}</h4>
				<ul class="similar">
					{foreach from=$similarRecords item=similar}
					<li>
						{if is_array($similar.format)}
							<span class="{$similar.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
						{else}
							<span class="{$similar.format|lower|regex_replace:"/[^a-z0-9]/":""}">
						{/if}
						<a href="{$path}/Record/{$similar.id|escape:"url"}">{$similar.title|regex_replace:"/(\/|:)$/":""|escape}</a>
						</span>
						{if $similar.author}
						<span style="font-size: 80%">
						<br/>{translate text='By'}: {$similar.author|escape}
						</span>
						{/if}
					</li>
					{/foreach}
				</ul>
			</div>
		 {/if}
	</div>

	<div class="sidegroup" id="similarAuthorsSidegroup">
		<div id="similarAuthorPlaceholder"></div>
	</div>
	*}

	{*
	{if is_array($otherEditions) }
		<div class="sidegroup" id="otherEditionsSidegroup">
			<h4>{translate text="Other Editions"}</h4>
			{foreach from=$otherEditions item=edition}
				<div class="sidebarLabel">
					<a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|regex_replace:"/(\/|:)$/":""|escape}</a>
				</div>
				<div class="sidebarValue">
					<div>
						{if is_array($edition.format)}
							{foreach from=$edition.format item=format name=loop}
								{if !$smarty.foreach.loop.first}, {/if}
								{$format}
							{/foreach}
						{else}
							{$edition.format}
						{/if}
					</div>
				{$edition.edition|escape}
				{if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
				</div>
			{/foreach}
		</div>
	{/if}
	*}
	
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