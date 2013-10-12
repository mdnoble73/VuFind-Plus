{strip}
<div class="searchHome">
	Welcome to the home page!
	<div class="searchHomeContent">
		{if $widgets}
			<div id="homePageLists">
				{foreach from=$widgets item=widget}
					{include file='API/listWidgetTabs.tpl'}
				{/foreach}
			</div>
		{else}
			<img src = "{if $largeLogo}{$largeLogo}{else}{img filename="logo_large.png"}{/if}" alt='{$librarySystemName} Logo'/>
		{/if}
		
		<div class="searchHomeForm">
			<div id='homeSearchLabel'>Search the {$librarySystemName} Catalog</div>
			{include file="Search/searchbox.tpl"}
		</div>
		
		
	</div>
</div>
{/strip}