<!DOCTYPE html>
<html lang="{$userLang}">
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>{$pageTitle|truncate:64:"..."}</title>
    {if $addHeader}{$addHeader}{/if}
    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe" />
    {if $consolidateCss}
      {css filename="consolidated_css.css"}
    {else}
      {css filename="jqueryui.css"}
      {css filename="styles.css"}
      {css filename="basicHtml.css"}
      {css filename="top-menu.css"}
      {css filename="title-scroller.css"}
      {css filename="my-account.css"}
      {css filename="holdingsSummary.css"}
      {css filename="ratings.css"}
      {css filename="book-bag.css"}
      {css filename="jquery.tooltip.css"}
      {css filename="tooltip.css"}
      {css filename="record.css"}
      {css filename="search-results.css"}
      {css filename="suggestions.css"}
      {css filename="reports.css"}
      {css filename="dcl.css"}
      {css filename="anythink2.css"}
    {/if}
    {css media="print" filename="print.css"}
    <script type="text/javascript">
      path = '{$path}';
      loggedIn = {if $user}true{else}false{/if};
    </script>
    {if $consolidateJs}
      <script type="text/javascript" src="{$path}/API/ConsolidatedJs"></script>
    {else}
      <script type="text/javascript" src="{$path}/js/jquery-1.7.1.min.js"></script>
      <script type="text/javascript" src="{$path}/js/jqueryui/jquery-ui-1.8.18.custom.min.js"></script>
      <script type="text/javascript" src="{$path}/js/jquery.plugins.js"></script>
      <script type="text/javascript" src="{$path}/js/scripts.js"></script>
      <script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
      {if $enableBookCart}
      <script type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
      <script type="text/javascript" src="{$path}/interface/themes/anythink2/js/bookcart-anythink2.js"></script>
      {/if}
      {* Code for description pop-up and other tooltips.*}
      <script type="text/javascript" src="{$path}/js/title-scroller.js"></script>
      <script type="text/javascript" src="{$path}/services/Search/ajax.js"></script>
      <script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
      <script type="text/javascript" src="{$path}/js/overdrive.js"></script>
      {* Formalize *}
      <script src="{$path}/interface/themes/anythink2/js/jquery.formalize.min.js" type="text/javascript"></script>
      <script src="{$path}/interface/themes/anythink2/js/anythink2.js" type="text/javascript"></script>
    {/if}
    {* Files that should not be combined *}
    {if $includeAutoLogoutCode == true}
      <script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
    {/if}
    {if isset($theme_css)}
    <link rel="stylesheet" type="text/css" href="{$theme_css}" />
    {/if}
    <script type="text/javascript">
      {literal}
      (function($) {
        // General settings for this theme.
        anythink = {settings: {}};
        // Hold message.
        anythink.settings.hold_message = '{/literal}{if $hold_message}{$hold_message|escape:"javascript"}{/if}{literal}';
        // Renew message.
        anythink.settings.renew_message = '{/literal}{if $renew_message}{$renew_message|escape:"javascript"}{/if}{literal}';
        $(document).ready(function(){
          // Show hold message if set.
          if (anythink.settings.hold_message != '') {
            lightbox();
            $('#popupbox').html(anythink.settings.hold_message);
          };
          // Show renew message if set.
          if (anythink.settings.renew_message != '') {
            lightbox();
            $('#popupbox').html(anythink.settings.renew_message);
          };
        });
      })(jQuery);
      {/literal}
    </script>
    <script src="{$path}/interface/themes/anythink2/js/anythink-survey.js" type="text/javascript"></script>
  </head>
  <body class="{$module} {$action} {$module}--{$action} {$module}--{$action}--{$recordCount}">
    <div style="text-align: center; background-color: #B31E3B; color: #FFF; padding: .5em 0; font-size: 1.2em;">It's Anythink's new catalog! Tell us what you think. We're still testing and tweaking, so pardon us if you find a bug.</div>
    <div id="container"><div id="inner">
      <!-- Current Physical Location: {$physicalLocation} -->
      {* LightBox *}
      <div id="lightboxLoading" class="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
      <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
      <div id="lightbox" onclick="hideLightbox(); return false;"></div>
      <div id="popupbox" class="popupBox"></div>
      {* End LightBox *}
      <div class="{$page_body_style}">
        <div id="header">
          {* This needs to be heavily refactored.
          {if $user}
            <span id="myAccountNameLink" class="menu-account-link logoutOptions top-menu-item"><a href="{$path}/MyAccount/Home">{if $user->displayName}{$user->displayName}{else}{$user->firstname|capitalize} {$user->lastname|capitalize}{/if}</a></span>
            <span class="menu-account-link logoutOptions top-menu-item"><a href="{$path}/MyAccount/Logout">{translate text="Log Out"}</a></span>
            <span class="menu-account-link loginOptions top-menu-item"><a href="{$path}/MyAccount/Home">{translate text="My Account"}</a></span>
          {else}
            <span class="menu-account-link loginOptions top-menu-item"><a href="{$path}/MyAccount/Home">{translate text="My Account"}</a></span>
          {/if}
          *}
          <a id="logo" href="{if $homeLink}{$homeLink}{else}{$url}{/if}">{translate text="Anythink Libraries"}</a>
          <div id="header-utility-top">
            <ul class="inline right">
            {if !empty($allLangs) && count($allLangs) > 1}
               {foreach from=$allLangs key=langCode item=langName}
                 <li><a class='languageLink {if $userLang == $langCode} selected{/if}' href="{$fullPath|escape}{if $requestHasParams}&amp;{else}?{/if}mylang={$langCode}">{translate text=$langName}</a></li>
               {/foreach}
            {/if}
            {* Link to Search Tips Help *}
            <li><a href="{$url}/Help/Home?topic=search" title="{translate text='Search Tips'}" onclick="window.open('{$url}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">
              Help <img id='searchHelpIcon' src="{$path}/interface/themes/default/images/help.png" alt="{translate text='Search Tips'}" />
            </a></li>
            </ul>
          </div>
          {if !($module == 'Search' && $action == 'Home') && $pageTemplate != 'advanced.tpl'}
            {if $module=="Summon"}
              {include file="Summon/searchbox.tpl"}
            {elseif $module=="WorldCat"}
              {include file="WorldCat/searchbox.tpl"}
            {else}
              {include file="Search/searchbox.tpl"}
            {/if}
          {/if}
          <div id="header-utility-bottom">
            <ul class="inline right">
              {if !$user}
                <li><a href="{$path}/MyAccount/Home">{translate text="My Account"}</a></li>
                <li><a href="{$path}/MyAccount/GetCard">{translate text="Get a Card"}</a></li>
              {else}
                <li><a href="{$path}/MyAccount/Home">{translate text="My Account"}</a></li>
                <li><a href="{$path}/MyAccount/Logout">{translate text="Log Out"}</a></li>
              {/if}
            </ul>
          </div>
          {if $useSolr || $useWorldcat || $useSummon}
            <ul>
              {if $useSolr}
              <li{if $module != "WorldCat" && $module != "Summon"} class="active"{/if}><a href="{$url}/Search/Results?lookfor={$lookfor|escape:"url"}">{translate text="University Library"}</a></li>
              {/if}
              {if $useWorldcat}
              <li{if $module == "WorldCat"} class="active"{/if}><a href="{$url}/WorldCat/Search?lookfor={$lookfor|escape:"url"}">{translate text="Other Libraries"}</a></li>
              {/if}
              {if $useSummon}
              <li{if $module == "Summon"} class="active"{/if}><a href="{$url}/Summon/Search?lookfor={$lookfor|escape:"url"}">{translate text="Journal Articles"}</a></li>
              {/if}
            </ul>
          {/if}
          {if !($module == 'Search' && $action == 'Home')}
            <a id="navigate-link" href="http://www.anythinklibraries.org/">{translate text="Explore Anythink..."}</a>
          {/if}
        </div>
        <div id="central" class="clearfix{if $module == 'Search' && $action == 'Home'} with-column-outer{/if}">
          {if $module == 'Search' && $action == 'Home'}
          <div id="column-outer-wrapper"><div id="column-outer">
            <iframe width="200" height="700" border="0" src="http://www.anythinklibraries.org/vufind/sidebar"></iframe>
          </div></div>
          {/if}
          <div id="column-central">
            <h4 id="flag">{translate text="Catalog"}</h4>
            <div id="main-wrapper"><div id="main" class="debug {$module}--{$pageTemplate} clearfix">
                <div id="fixed-wrapper">
                  {include file="bookcart.tpl"}
                </div>
                {if $showBreadcrumbs}
                  <div id="breadcrumb">
                    <a href="{$url}">{translate text="Catalog"}</a> <span>&gt;</span>
                    {include file="$module/breadcrumbs.tpl"}
                  </div>
                {/if}
              {include file="$module/$pageTemplate"}
            </div></div>
          </div>
        </div>
        <div id="footer">
        </div>
      </div>
    </div></div>

  {* Google Analytics *}
  {if $productionServer}
  <script type="text/javascript">
  {literal}
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-8977686-8']);
    _gaq.push(['_trackPageview']);
    _gaq.push(['_trackPageLoadTime']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
  {/literal}
  </script>
  {/if}
  </body>
</html>
