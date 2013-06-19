{strip}
<div class="row-fluid searchHome">
	<div class="span12 text-center searchHomeContent">
		{if $widget}
			<div id="homePageLists">
				{include file='API/listWidgetTabs.tpl'}
			</div>
		{else}

			<img id="largeLogo" src="{if $largeLogo}{$largeLogo}{else}{img filename="logo_large.png"}{/if}" alt='{$librarySystemName} Logo'/>
		{/if}
		
		<div class="searchHomeForm">
			<h3 id='homeSearchLabel'>Search the {$librarySystemName} Catalog</h3>
			{include file="Search/searchbox.tpl"}
		</div>

	</div>
</div>
{/strip}