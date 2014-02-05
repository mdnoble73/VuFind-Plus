{strip}
<div class="searchheader">
	<div class="searchcontent">
    
		{include file='login-block.tpl'} 
      {*
        {literal}
<div id="google_translate_element"></div>
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, autoDisplay:false, gaTrack: true, gaId: 'UA-4349296-31'}, 'google_translate_element');
}

</script>

<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
{/literal}
        *}
        
       <!--
       <div class="tabs-container">   
       <div class="headerTabs">
            <ul class="tabsGroup">
                <li><a href="http://www.library.nashville.org/develop/vufind/booklists.asp">Book Lists</a></li>
                <li><a href="http://catalog.library.nashville.org/MyResearch/SuggestedTitles">Recommendations</a></li>
                <li><a href="http://www.surveymonkey.com/s/vufindplus_feedback">Feedback</a></li>
            </ul>
       </div>   
       </div>   
       --> 
       
          <a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Library Home" title="Return to Library Homepage" class="alignleft"  id="header_logo"/></a>
                
{*		{if $showTopSearchBox || $widget}
			<a href="{if $homeLink}{$homeLink}{else}{$path}/{/if}"><img src="{if $smallLogo}{$smallLogo}{else}{img filename="logo_small.png"}{/if}" alt="Library Home" title="Return to Library Homepage" class="alignleft"  id="header_logo"/></a>
		{/if}
            
*}

		{literal}
		<div id="google_translate_element"></div><script type="text/javascript">
			function googleTranslateElementInit() {
				new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
			}
		</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
		{/literal}

		<div class="clearer">&nbsp;</div>
        
	</div>
</div>
{/strip}
