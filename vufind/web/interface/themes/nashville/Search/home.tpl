{strip}

<div class="searchHome">

    <div class="searchHomeForm">
        {include file="searchbar.tpl"}
    </div>
        
	<div class="searchHomeContent">
<!--
		{if $widgets}
			<div id="homePageLists">
				{foreach from=$widgets item=widget}
                		    {include file='API/listWidgetTabs.tpl'}
                		{/foreach}
			</div>
		{/if}
-->

		<iframe src="http://catalog.library.nashville.org/API/SearchAPI?method=getListWidget&id=17" width="100%" height="320"></iframe>

	</div>

</div>
{/strip}
