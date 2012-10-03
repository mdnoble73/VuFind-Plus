<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang}">
{strip}
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title>{$pageTitle|truncate:64:"..."}</title>
		{if $addHeader}{$addHeader}{/if}
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon" />
		<link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$path}/Search/OpenSearch?method=describe" />
		{css filename="consolidated.min.css"}
		{if $theme_css}
			<link rel="stylesheet" href="{$theme_css}" type="text/css" media="screen" />
		{/if}
		<script type="text/javascript">
			path = '{$path}';
			url = '{$url}';
			loggedIn = {if $user}true{else}false{/if}
		</script>

		{js filename="consolidated.min.js"}

		{* Files that should not be combined *}
		{if $includeAutoLogoutCode == true}
		<script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
		{/if}
	</head>

	<body class="{$module} {$action}">
		{*- Set focus to the correct location by default *}
		{if $focusElementId}
		<script type="text/javascript">{literal}
		jQuery(function (){
			jQuery('#{/literal}{$focusElementId}{literal}').focus().select();
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

		{if $systemMessage}
		<div id="systemMessage">{$systemMessage}</div>
		{/if}
		<div id="pageBody" class="{$page_body_style}">
			<div id="outer_span">
				{include file="header.tpl"}

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
						emptyBag();
						document.getElementById('popupbox').innerHTML = "{$hold_message|escape:'javascript'}";
						</script>
					{/if}

					{if $renew_message}
						<script type="text/javascript">
						lightbox();
						document.getElementById('popupbox').innerHTML = "{$renew_message|escape:'javascript'}";
						</script>
					{/if}

					<div id="ft">
						{include file=$footerTemplate}
					</div> {* End ft *}
					<div class="clearer">&nbsp;</div>
				</div>
			</div> {* End outer_span *}
 		</div>
	</body>
</html>{/strip}