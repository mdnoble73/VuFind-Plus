{strip}
<div class="searchHome">
	<div class="searchHomeContent">
		{if $widget}
			<div id="homePageLists">
				{include file='API/listWidgetTabs.tpl'}
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