<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang}">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{$pageTitle|truncate:64:"..."}</title>
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon" />
    {css filename="consolidated.min.css"}
		{if $addHeader}{$addHeader}{/if}
		<link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$path}/Search/OpenSearch?method=describe" />
		<script type="text/javascript">
		path = '{$path}';
		url = '{$url}';
		loggedIn = {if $user}true{else}false{/if}
		</script>
		
		{js filename="consolidated.min.js"}
		<script src="http://www.coloradomesa.edu/js/megamenu.js" type="text/javascript"></script>
		
		{if $includeAutoLogoutCode == true}
		<script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
		{/if}
		
		{if isset($theme_css)}
		<link rel="stylesheet" type="text/css" href="{$theme_css}" />
		{/if}
	</head>

	<body class="{$module} {$action}" >
		{*- Set focus to the correct location by default *}
		{if $focusElementId}
		<script type="text/javascript">{literal}
		jQuery(function (){
			jQuery('#{/literal}{$focusElementId}{literal}').focus().select();
		});{/literal}
		</script>
		{/if}
		
		{if $systemMessage}
		<div id="systemMessage">{$systemMessage}</div>
		{/if}
		
		{include file="bookcart.tpl"}
	
		<!-- Current Physical Location: {$physicalLocation} -->
		{* LightBox *}
		<div id="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
		<div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
		<div id="lightbox" onclick="hideLightbox(); return false;"></div>
		<div id="popupbox" class="popupBox"></div>
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
					<li{if $module != "WorldCat" && $module != "Summon"} class="active"{/if}><a href="{$path}/Search/Results?lookfor={$lookfor|escape:"url"}">{translate text="University Library"}</a></li>
					{/if}
					{if $useWorldcat}
					<li{if $module == "WorldCat"} class="active"{/if}><a href="{$path}/WorldCat/Search?lookfor={$lookfor|escape:"url"}">{translate text="Other Libraries"}</a></li>
					{/if}
					{if $useSummon}
					<li{if $module == "Summon"} class="active"{/if}><a href="{$path}/Summon/Search?lookfor={$lookfor|escape:"url"}">{translate text="Journal Articles"}</a></li>
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