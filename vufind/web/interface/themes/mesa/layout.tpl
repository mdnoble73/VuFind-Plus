<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="{$userLang}">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
		<meta http-equiv="X-UA-Compatible" content="IE=8" >
		<title>{$pageTitle|truncate:64:"..."}</title>
		{if $addHeader}{$addHeader}{/if}
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon" />
		<link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe" >
		{css filename="consolidated.min.css"}
		<link rel="stylesheet" type="text/css" href="http://mesacountylibraries.org/wp-content/themes/mcpl/styles/superfish.css" />
			
		<script type="text/javascript">
			path = '{$path}';
			url = '{$url}';
			loggedIn = {if $user}true{else}false{/if}
		</script>
		
		{js filename="consolidated.min.js"}
		
		{* Files that should not be combined *}
		{if $includeAutoLogoutCode == true}
		<script	type="text/javascript" src="{$path}/js/autoLogout.js"></script>
		{/if}

	</head>

	<body class="{$module} {$action}" onload="{literal}if(document.searchForm != null && document.searchForm.lookfor != null){ document.searchForm.lookfor.focus();} if(document.loginForm != null){document.loginForm.username.focus();}{/literal}">
		{if $systemMessage}
		<div id="systemMessage">{$systemMessage}</div>
		{/if}
		{include file="bookcart.tpl"}
	
		<!-- Current Physical Location: {$physicalLocation} -->
		{* LightBox *}
		<div id="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
		<div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
		<div id="lightbox" onclick="hideLightbox(); return false;"></div>
		<div id="popupbox" class="popupBox"><b class="btop"><b></b></b></div>
		{* End LightBox *}
		
		<div class="searchheader">
			<div class="searchcontent">
				{include file='login-block.tpl'}
		{include file ='mesaheader.tpl'}
				

				<br clear="all">
				
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

			<div id="ft">
			{include file="footer.tpl"}
			</div> {* End ft *}

		</div> {* End doc *}
		{* include file ='mesafooter.tpl' *}
	</body>
</html>