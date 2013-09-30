{strip}
<div class="searchheader">
	<div class="searchcontent">
		{include file='login-block.tpl'} 
         
        <div id="headerButtons" class="alignright">
            <ul class="headerButtons">
                <li class="BrowseLists"><a href="#">Book Lists</a></li>
                <li class="Recommendations"><a href="http://catalog.library.nashville.org/MyResearch/SuggestedTitles">Recommendations</a></li>
                <li class="Online"><a href="#">Online Collection</a></li>
            </ul>
        </div>       
                
		{if $showTopSearchBox || $widget}
			<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/></a>
		{/if}
            

        
		<div class="clearer">&nbsp;</div>
        
	</div>
</div>
{/strip}