<!DOCTYPE html>
<html lang="{$userLang}">
	<head>
		<title>{$pageTitle|truncate:64:"..."}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		{if $addHeader}{$addHeader}{/if}
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon" />
		<link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$path}/Search/OpenSearch?method=describe" />

		{* Include css as appropriate *}
		<link href="{$path}/interface/themes/responsive/css/bootstrap.css" rel="stylesheet" media="screen">
		<link href="{$path}/interface/themes/responsive/css/bootstrap-responsive.css" rel="stylesheet" media="screen">
		<link href="{$path}/interface/themes/responsive/css/marmot.css" rel="stylesheet" media="screen">

		{* Include correct javascript *}
		<script type="text/javascript">
			path = '{$path}';
			url = '{$url}';
			loggedIn = {$loggedIn};
			automaticTimeoutLength = {$automaticTimeoutLength};
			automaticTimeoutLengthLoggedOut = {$automaticTimeoutLengthLoggedOut};
		</script>
		<script src="{$path}/js/jquery-1.9.1.min.js"></script>

		{if $includeAutoLogoutCode == true}
			<script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
		{/if}
	</head>
	<body class="module_{$module} action_{$action}">
		<div class="container-fluid">
			{include file='header.tpl'}

			{if $showBreadcrumbs}
				<ul class="breadcrumb">
					<li><a href="{$homeBreadcrumbLink}"><span class="home-icon">&nbsp;</span> {translate text=$homeLinkText}</a> <span class="divider">&raquo;</span></li>
					{include file="$module/breadcrumbs.tpl"}
				</ul>
			{/if}

			{include file="$module/$pageTemplate"}

			{include file="footer.tpl"}
		</div>

		{include file="tracking.tpl"}

		{* Extra javascript at end so the pages loose faster. *}
		<script src="{$path}/interface/themes/responsive/js/bootstrap.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-transition.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-alert.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-modal.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-dropdown.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-scrollspy.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-tab.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-tooltip.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-popover.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-button.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-collapse.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-carousel.js"></script>
		<script src="{$path}/interface/themes/responsive/js/bootstrap-typeahead.js"></script>
	</body>
</html>