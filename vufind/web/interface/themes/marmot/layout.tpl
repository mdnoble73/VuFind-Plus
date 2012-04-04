<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang}">{strip}
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>{$pageTitle|truncate:64:"..."}</title>
    {if $addHeader}{$addHeader}{/if}
    <link type="image/x-icon" href="{$path}/interface/themes/{$theme}/images/favicon.png" rel="shortcut icon" />
    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe" />
    {if $consolidateCss}
      {css filename="consolidated_css.css"}
    {else}
	    {css filename="basicHtml.css"}
	    {css filename="jqueryui.css"}
    	{css media="screen" filename="styles.css"}
    	{css media ="screen" filename="book-bag.css"}
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
	  	{css filename="prospector.css"}
	  	{css filename="marmot.css"}
    {/if}
    {css media="print" filename="print.css"}
    	
    <script type="text/javascript">
      path = '{$path}';
      url = '{$url}';
      loggedIn = {if $user}true{else}false{/if}
    </script>
    
    {if $consolidateJs}
    	<script type="text/javascript" src="{$path}/API/ConsolidatedJs"></script>
    {else}
	    <script type="text/javascript" src="{$path}/js/yui/yahoo-dom-event.js"></script>
	    <script type="text/javascript" src="{$path}/js/yui/yahoo-min.js"></script>
	    <script type="text/javascript" src="{$path}/js/yui/event-min.js"></script>
	    <script type="text/javascript" src="{$path}/js/yui/connection-min.js"></script>
	    <script type="text/javascript" src="{$path}/js/yui/dragdrop-min.js"></script>
	    <script type="text/javascript" src="{$path}/js/scripts.js"></script>
	    <script type="text/javascript" src="{$path}/js/rc4.js"></script>
	
	    
	    <script type="text/javascript" src="{$path}/js/jquery-1.7.1.min.js"></script>
	    <script type="text/javascript" src="{$path}/js/jqueryui/jquery-ui-1.8.18.custom.min.js"></script>
	    
	    {if $enableBookCart}
	    <script type="text/javascript" src="{$path}/js/bookcart/jquery.blockUI.js"></script>
	    <script type="text/javascript" src="{$path}/js/bookcart/json2.js"></script>
	    <script type="text/javascript" src="{$path}/js/bookcart/jquery.cookie.js"></script>
		  <script type="text/javascript" src="{$path}/js/bookcart/bookcart.js"></script>
		  {/if}
	    
	    <script type="text/javascript" src="{$path}/js/ajax.yui.js"></script>
	    <script type="text/javascript" src="{$path}/js/dropdowncontent.js"></script>
	    <script type="text/javascript" src="{$path}/js/tabs/tabcontent.js"></script>

		  <script type="text/javascript" src="{$path}/js/starrating/jquery.rater.js"></script>
		  
		  <script type="text/javascript" src="{$path}/js/autofill.js"></script>
		
		  <script type="text/javascript" src="{$path}/js/tooltip/lib/jquery.bgiframe.js"></script>
		  <script type="text/javascript" src="{$path}/js/tooltip/jquery.tooltip.js"></script>
		  
		  <script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js"></script>
		  
			<script type="text/javascript" src="{$path}/js/ajax_common.js"></script>
			<script type="text/javascript" src="{$path}/services/Search/ajax.js"></script>
			<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
			<script type="text/javascript" src="{$path}/js/description.js"></script>
			
			<script type="text/javascript" src="{$path}/js/overdrive.js"></script>
		  
    {/if}
    
    {* Files that should not be combined *}
    {if false && !$productionServer}
    <script type="text/javascript" src="{$path}/js/errorHandler.js"></script>
    {/if}
    {if $includeAutoLogoutCode == true}
    <script type="text/javascript" src="{$path}/js/jquery.idle-timer.js"></script>
    <script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
    {/if}
  
    
    {if isset($theme_css)}
    <link rel="stylesheet" type="text/css" href="{$theme_css}" >
    {/if}
  </head>

  <body class="{$module} {$action}">
    {*- Set focus to the correct location by default *}
    {if $focusElementId}
    <script type="text/javascript">{literal}
    jQuery(function (){
      jQuery('#{/literal}{$focusElementId}{literal}').focus();
    });{/literal}
    </script>
    {/if}
   {include file="bookcart.tpl"}
  
    <!-- Current Physical Location: {$physicalLocation} -->
    {* LightBox *}
    <div id="lightboxLoading" class="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
    <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
    <div id="lightbox" onclick="hideLightbox(); return false;"></div>
    <div id="popupbox" class="popupBox"></div>
    {* End LightBox *}
    
    <div id="pageBody" class="{$page_body_style}">
    
    <div class="searchheader">
      <div class="searchcontent">
        {include file='login-block.tpl'}

        {if $showTopSearchBox}
          <a href="{if $homeLink}{$homeLink}{else}{$url}{/if}"><img src="{$path}{$smallLogo}" alt="Catalog Home" title="Return to Catalog Home" class="alignleft" /></a>
        {/if}

        <div class="clearer">&nbsp;</div>
        
        {if $showTopSearchBox}
          <div id='searchbar'>
          {if $pageTemplate != 'advanced.tpl'}
            {include file="searchbar.tpl"}
          {/if}
          </div>
        {/if}
      </div>
    </div>
    
    {if $showBreadcrumbs}
    <div class="breadcrumbs">
      <div class="breadcrumbinner">
        <a href="{$homeBreadcrumbLink}">{translate text="Home"}</a> <span>&gt;</span>
        {include file="$module/breadcrumbs.tpl"}
      </div>
    </div>
    {/if}
    
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
      
      {if $renew_message}
        <script type="text/javascript">
        lightbox();
        document.getElementById('popupbox').innerHTML = "{$renew_message|escape:"javascript"}";
        </script>
      {/if}
      
      <div id="ft">
	      	{include file="footer.tpl"}
	    </div> {* End ft *}
    </div> {* End page body *}
 
  </body>
</html>{/strip}