<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang}">
  <head>
  <meta http-equiv="X-UA-Compatible" content="IE=8" />
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>{$pageTitle|truncate:64:"..."}</title>
    {if $addHeader}{$addHeader}{/if}
    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe" />
    {if $consolidateCss}
    	 {css filename="consolidated.css"}
    {else}
    	{css filename="jqueryui.css"}
	    {css filename="styles.css"}
	    {css filename="basicHtml.css"}
			{css filename="header.css"}
			{css filename="library-footer.css"}
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
			{css filename="brian_help.css"}
			{css filename="wcpl.css"}
		{/if}
	
    {css media="print" filename="print.css"}
    
    <script type="text/javascript">
      path = '{$path}';
      loggedIn = {if $user}true{else}false{/if}

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
    <script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
	  <script type="text/javascript" src="{$path}/services/Search/ajax.js"></script>
	  {/if}

		{* Files that should not be combined *}
    {if $includeAutoLogoutCode == true}
    <script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
    {/if}
    
    <!-- ben: brian help script -->
    {js filename="brian_help.js"}
    {js filename="wcpl.js"}
    
    {if isset($theme_css)}
    <link rel="stylesheet" type="text/css" href="{$theme_css}" />
    {/if}
  </head>

  <body class="{$module} {$action}">
    {*- Set focus to the correct location by default *}
    <script type="text/javascript">{literal}
    $(function (){
      $('#{/literal}{$focusElementId}{literal}').focus();
    });{/literal}
    </script>
    
    <!-- Current Physical Location: {$physicalLocation} -->
    {* LightBox *}
    <div id="lightboxLoading" class="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
    <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
    <div id="lightbox" onclick="hideLightbox(); return false;"></div>
    <div id="popupbox" class="popupBox"><b class="btop"><b></b></b></div>
    {* End LightBox *}
    
    {include file="bookcart.tpl"}
    
    <div id="pageBody" class="{$page_body_style}">
    
    {* The top of the page*}
    {include file="header.tpl"}
    
    {if $showBreadcrumbs}
    <div class="breadcrumbs">
      <div class="breadcrumbinner">
        <a href="{$url}">{translate text="New Search"}</a> <span>&gt;</span>
        {include file="$module/breadcrumbs.tpl"}
      </div>
    </div>
    {/if}
    
    {* The main contents of the page *}
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

    {* The page footer *}
    {include file="library-footer.tpl"}
    </div> {* End page body *}
    
  {* add analytics tracking code*}
	{if $productionServer}{literal}
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-24700199-1']);
	  _gaq.push(['_trackPageview']);
	  _gaq.push(['_trackPageLoadTime']);
	
	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	
	</script>
	{/literal}{/if}  
  </body>
</html>