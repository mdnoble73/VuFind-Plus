{strip}

<div class="searchHome">

    <div class="searchHomeForm">
        {include file="searchbar.tpl"}
    </div>
        
	<div class="searchHomeContent">
		{if $widgets}
			<div id="homePageLists">
				{foreach from=$widgets item=widget}
                    {include file='API/listWidgetTabs.tpl'}
                {/foreach}
			</div>
		{/if}
	</div>

</div>
{/strip}
