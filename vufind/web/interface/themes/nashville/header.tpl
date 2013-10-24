{strip}
<div class="searchheader">
	<div class="searchcontent">
    
		{include file='login-block.tpl'} 
         
       <div id="headerButtons" class="alignright">
            <ul class="headerButtons">
                {* <li><a href="#">Book Lists</a></li>
                <li><a href='http://catalog.library.nashville.org/MyResearch/SuggestedTitles'>Recommendations</a></li> *}
                <li><a href="http://www.surveymonkey.com/s/vufindplus_feedback" target="_blank">Feedback</a></li>
            </ul>
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