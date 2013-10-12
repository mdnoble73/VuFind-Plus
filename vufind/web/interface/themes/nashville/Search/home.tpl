{strip}
<div class="searchHome">

    <div class="searchHomeForm">
        {include file="Search/searchbox.tpl"}
    </div>
        
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
	</div>

</div>


{/strip}