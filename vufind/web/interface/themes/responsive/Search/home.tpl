{strip}
<div class="row-fluid searchHome">
	<div class="span12 text-center searchHomeContent">
		{if $widget}
			<div id="homePageLists">
				{include file='API/listWidgetTabs.tpl'}
			</div>
		{else}
			<img id="largeLogo" src="{if $largeLogo}{$largeLogo}{else}{img filename="logo_large.png"}{/if}" alt='{$librarySystemName} Logo' class="hidden-phone"/>
		{/if}
		
		<div class="searchHomeForm">
			{include file="Search/searchbox.tpl" showAsBar=false}
		</div>

	</div>
</div>
{/strip}