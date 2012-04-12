<!DOCTYPE html>
<html lang="{$userLang}">
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>{$pageTitle}</title>
    {if $addHeader}{$addHeader}{/if}
    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe" />
    {css filename="styles.css"}
    {css filename="basicHtml.css"}
  	{css filename="top-menu.css"}
  	{css filename="library-footer.css"}
  	{css filename="my-account.css"}
  	{css filename="holdingsSummary.css"}
  	{css filename="book-bag.css"}
  	{css filename="jquery.tooltip.css"}
  	{css filename="tooltip.css"}
  	{css filename="record.css"}
  	{css filename="search-results.css"}
  	{css filename="suggestions.css"}
  	{css filename="reports.css"}
		<link rel="stylesheet" href="{$path}/interface/themes/anythink/css/anythink.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="{$path}/js/jqueryui/ui-lightness/jquery-ui-1.8.11.custom.css" type="text/css" />
    
		{css filename="title-scroller.css"}
		{css filename="ratings.css"}
    <script type="text/javascript">
      <!--
      var path = '{$path}';
      var url = '{$url}';
      var loggedIn = {if $user}true{else}false{/if};
      // Placeholder for settings.
      var anythink = {literal}{settings: {}}{/literal};
      anythink.settings.path = '{$path}';
      anythink.settings.request_time = {$smarty.now};
      // -->
    </script>

    {if $consolidateJs}
      <script type="text/javascript" src="{$path}/API/ConsolidatedJs"></script>
    {else}
      <script type="text/javascript" src="{$path}/js/jquery-1.7.1.min.js"></script>
      <script type="text/javascript" src="{$path}/js/jqueryui/jquery-ui-1.8.18.custom.min.js"></script>
      <script type="text/javascript" src="{$path}/js/jquery.plugins.js"></script>
      <script type="text/javascript" src="{$path}/js/scripts.js"></script>

      {if $enableBookCart}
      <script type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
      <script type="text/javascript" src="{$path}/js/bookcart/bookcart.js"></script>
      {/if}

      {* Code for description pop-up and other tooltips.*}
      <script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
      <script type="text/javascript" src="{$path}/services/Search/ajax.js"></script>
      <script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>

      <script type="text/javascript" src="{$path}/js/overdrive.js"></script>
    {/if}

    {* Files that should not be combined *}
    {*
    {if $includeAutoLogoutCode == true}
      <script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
    {/if}
    <script src="{$path}/interface/themes/anythink/js/jquery.formalize.min.js" type="text/javascript"></script>
    <script type="text/javascript">
    {literal}
      $(document).ready(function() {
        $('#help-link').bind('click', function(event) {
          window.open(url + '/Help/Home?topic=search', 'Help', 'width=625, height=510');
          return false;
        });
      });
    {/literal}
    </script>
    <script src="{$path}/interface/themes/anythink/js/anythink.js" type="text/javascript"></script>
  </head>
  <body class="{$module}-{$action} {$module} {$action}">
    <div id="wrapper"><div id="container"><div id="inner">
      <div id="header" class="cf">
        <script type="text/javascript">
        {literal}
        //jQuery(function (){
          //jQuery('#{/literal}{$focusElementId}{literal}').focus();
        //});
        {/literal}
        </script>
        
        <div id="lightboxLoading" class="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
        <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
        <div id="lightbox" onclick="hideLightbox(); return false;"></div>
        <div id="popupbox" class="popupBox"></div>
        
        {*include file="bookcart.tpl"*}
        <div id="branding">
          <h1><a href="http://www.anythinklibraries.org" title="Anythink. A revolution of Rangeview Libraries.">Anythink</a></h1>
          <p>A revolution of Rangeview Libraries</p>
        </div>
        <div id="utility-wrapper">
          <ul id="utility-links" class="inline right">
             {foreach from=$allLangs key=langCode item=langName}
               <li><a {if $userLang == $langCode}class='selected'{/if} href="{$fullPath|escape}{if $requestHasParams}&amp;{else}?{/if}mylang={$langCode}">{translate text=$langName}</a></li>
             {/foreach}
            {* Link to Search Tips Help *}
            <li><a id="help-link" href="{$url}/Help/Home?topic=search" title="{translate text='Search Tips'}">{translate text='Help'}<span>?</span></a></li>
          </ul>

          {if $pageTemplate != 'advanced.tpl'}
            {if $module=="Summon"}
              {include file="Summon/searchbox.tpl"}
            {elseif $module=="WorldCat"}
              {include file="WorldCat/searchbox.tpl"}
            {else}
              {include file="Search/searchbox.tpl"}
            {/if}
          {/if}
        </div>
        <ul id="mini-nav">
          <li id="mini-nav-home"><a href="http://www.anythinklibraries.org"><span>Main Anythink Website</span></a></li>
          <li id="mini-nav-catalog"><a href="/"><span>Catalog home</span></a></li>
        </ul>
        <div id="utility-links-secondary-wrapper">
          <ul id="utility-links-secondary" class="inline right">
            <li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
            {if $showAdvancedSearchbox == 1}
              <li><a href="{$path}/Search/Advanced">{translate text="Advanced Search"}</a></li>
            {/if}
          </ul>
        </div>
      </div>
      <div id="central" class="cf{if $module == 'Search' && $action == 'Home'} with-column-outer{/if}">
        {if $module == 'Search' && $action == 'Home'}
        <div id="column-outer-wrapper"><div id="column-outer">
          {include file="Search/menu-temp.tpl"}
        </div></div>
        {/if}
        <div id="column-central">
          <h4 id="flag">{translate text="Welcome to the Anythink catalog"}</h4>
          <div id="main-wrapper"><div id="main" class="debug {$module}-{$pageTemplate} cf">
            {if $hold_message}
            HOLD MESSAGE
            {$hold_message}
            {/if}
            {if $renew_message}
            RENEW MESSAGE
            {$renew_message}
            {/if}
            {include file="$module/$pageTemplate"}
          </div></div>
        </div>
      </div>
    </div>
    <div id="footer">
      {include file="library-footer.tpl"}
    </div>
  </div>
</div>

    {if $productionServer}
      {literal}
      <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-8977686-8']);
        _gaq.push(['_trackPageview']);
        _gaq.push(['_trackPageLoadTime']);
        (function() {
          var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
          ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
          var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();
      </script> 
      {/literal}
      {* Strands tracking *}
      {if $strandsAPID}
        {if $user && $user->disableRecommendations == 0}
          {literal}
          <script type="text/javascript">
          //This code can actually be used anytime to achieve an "Ajax" submission whenever called
          if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}
          StrandsTrack.push({
             event:"userlogged",
             user: "{/literal}{$user->id}{literal}"
          });
          </script>
          {/literal}
          <!-- Strands Library MUST be included at the end of the HTML Document, before the /body closing tag and JUST ONCE -->
          {literal}
          <script type="text/javascript" src="http://bizsolutions.strands.com/sbsstatic/js/sbsLib-1.0.min.js"></script>
          <script type="text/javascript">
            try{ SBS.Worker.go("{$strandsAPID}"); } catch (e){};
          </script>
          {/literal}
        {/if}
      {/if}
    {/if}
  </body>
</html>