<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="{$userLang}">
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
    <meta http-equiv="X-UA-Compatible" content="IE=8" >
    <title>{$pageTitle|truncate:64:"..."}</title>
    <link rel="stylesheet" href="{$path}/interface/themes/msc/css/cmu_layout.css" type="text/css" >
    <link rel="stylesheet" href="{$path}/interface/themes/msc/css/cmu_setup.css" type="text/css" >
    <link rel="stylesheet" href="{$path}/interface/themes/msc/css/cmu_top_navigation_layout.css" type="text/css" >
    {if $consolidateCss}
      {css filename="consolidated_css.css"}
    {else}
	    <link rel="stylesheet" href="{$path}/js/autocomplete/jquery.autocomplete.css" type="text/css" >
	    <link rel="stylesheet" href="{$path}/js/jqueryui/flick/jquery-ui-1.8.8.custom.css" type="text/css" >
    	{css media="screen" filename="styles.css"}
    	{css media ="screen" filename="book-bag.css"}
    {/if}
    {if $addHeader}{$addHeader}{/if}
    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe" >
    <script language="JavaScript" type="text/javascript">
      path = '{$url}';
    </script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/yahoo-dom-event.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/yahoo-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/event-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/connection-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/dragdrop-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/scripts.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/rc4.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/services/Record/ajax.js"></script>

    
    <script language="JavaScript" type="text/javascript" src="{$path}/js/jquery-1.5.1.min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/jqueryui/jquery-ui-1.8.8.custom.min.js"></script>
    <script src="http://www.coloradomesa.edu/js/megamenu.js" type="text/javascript"></script>
    
    {if $enableBookCart}
    <script language="JavaScript" type="text/javascript" src="{$path}/js/bookcart/jquery.blockUI.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/bookcart/jquery.cookie.js"></script>
	  <script language="JavaScript" type="text/javascript" src="{$path}/js/bookcart/bookcart.js"></script>
	  {/if}
    
    <script language="JavaScript" type="text/javascript" src="{$path}/js/ajax.yui.js"></script>
    <script language="Javascript" type="text/javascript" src="{$path}/js/dropdowncontent.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/tabs/tabcontent.js"></script>
    {if false && !$productionServer}
    <script language="JavaScript" type="text/javascript" src="{$path}/js/errorHandler.js"></script>
    {/if}
    {if $includeAutoLogoutCode == true}
    <script language="JavaScript" type="text/javascript" src="{$path}/js/jquery.idle-timer.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/autoLogout.js"></script>
    {/if}
  
  <script type="text/javascript" src="{$path}/js/starrating/jquery.rater.js"></script>
  
  <script type="text/javascript" src="{$path}/js/autocomplete/lib/jquery.bgiframe.min.js"></script>
  <script type="text/javascript" src="{$path}/js/autocomplete/jquery.autocomplete.js"></script>
  <script type="text/javascript" src="{$path}/js/autofill.js"></script>

  <script type="text/javascript" src="{$path}/js/tooltip/lib/jquery.bgiframe.js"></script>
  <script type="text/javascript" src="{$path}/js/tooltip/lib/jquery.dimensions.js"></script>
  <script type="text/javascript" src="{$path}/js/tooltip/jquery.tooltip.js"></script>
  
  <script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js"></script>
  
  
    
    {if isset($theme_css)}
    <link rel="stylesheet" type="text/css" href="{$theme_css}" >
    {/if}
  </head>

  <body class="{$module} {$action}" onload="{literal}if(document.searchForm != null && document.searchForm.lookfor != null){ document.searchForm.lookfor.focus();} if(document.loginForm != null){document.loginForm.username.focus();}{/literal}">
   {include file="bookcart.tpl"}
  
    <!-- Current Physical Location: {$physicalLocation} -->
    {* LightBox *}
    <div id="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
    <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
    <div id="lightbox" onClick="hideLightbox(); return false;"></div>
    <div id="popupbox" class="popupBox"><b class="btop"><b></b></b></div>
    {* End LightBox *}
    
    {include file="header.tpl"}
    
    <div id="outer_span">
    <div id="content_span">
    {if $showTopSearchBox}
      <div id='searchbar'>
      {if $pageTemplate != 'advanced.tpl'}
        {include file="searchbar.tpl"}
      {/if}
      </div>
    {/if}
    
    {if $showBreadcrumbs}
    <div class="breadcrumbs">
      <div class="breadcrumbinner">
        <a href="{$homeBreadcrumbLink}">{translate text="Home"}</a> <span>&gt;</span>
        {include file="$module/breadcrumbs.tpl"}
      </div>
    </div>
    {/if}
    
    <div id="doc2" class="yui-t4"> {* Change id for page width, class for menu layout. *}

      {if $useSolr || $useWorldcat || $useSummon}
      <div id="toptab">
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
      </div>
      <div style="clear: left;"></div>
      {/if}

			{include file="$module/$pageTemplate"}
      
      {if $hold_message}
        <script type="text/javascript">
        lightbox();
        document.getElementById('popupbox').innerHTML = "{$hold_message|escape:"javascript"}";
        </script>
      {/if}

    </div> {* End doc *}
    </div> {* End content span *}
    
    <div id="ft">
      <div id="ft_contents">
        {include file="footer.tpl"}
      </div>
      <div class='clearer' ></div>
    </div> {* End ft *}
    
    </div> {* End outer_span *}
    

  </body>
</html>