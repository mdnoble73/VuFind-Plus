{strip}
<div class="searchheader">
	<div class="searchcontent">
    
		{include file='login-block.tpl'} 
       <div class="tabs-container">   
       <div class="headerTabs">
            <ul class="tabsGroup">
                <li><a href="http://www.library.nashville.org/develop/vufind/booklists.asp">Book Lists</a></li>
                <li><a href="http://catalog.library.nashville.org/MyResearch/SuggestedTitles">Recommendations</a></li>
                <li><a href="http://www.surveymonkey.com/s/vufindplus_feedback">Feedback</a></li>
            </ul>
        </div>   
        </div>    
       
          <a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Library Home" title="Return to Library Homepage" class="alignleft"  id="header_logo"/></a>
                
{*		{if $showTopSearchBox || $widget}
			<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Library Home" title="Return to Library Homepage" class="alignleft"  id="header_logo"/></a>
		{/if}
            
*}
        
		<div class="clearer">&nbsp;</div>
        
	</div>
</div>
{/strip}
