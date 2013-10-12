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
        
                
		{if $showTopSearchBox || $widget}
			<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft"  id="header_logo"/></a>
		{/if}
            

        
		<div class="clearer">&nbsp;</div>
        
	</div>
</div>
{/strip}