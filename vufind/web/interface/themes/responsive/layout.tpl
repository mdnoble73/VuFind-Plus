<!DOCTYPE html>
<html lang="{$userLang}">
	<head>
		<title>{$pageTitle|truncate:64:"..."}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		{if $google_translate_key}
			<meta name="google-translate-customization" content="{$google_translate_key}" />
		{/if}
		{if $addHeader}{$addHeader}{/if}
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon" />
		<link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$path}/Search/OpenSearch?method=describe" />

		{* Include css as appropriate *}
		{css filename="main.css"}
		{* <link href="{$path}/interface/themes/responsive/css/marmot.css" rel="stylesheet" media="screen"> *}

		{* Include correct all javascript *}
		{* TODO: Somehow minify all of this into one little file *}
		<script src="{$path}/js/jquery-1.9.1.min.js"></script>
		{* Load Libraries*}
		<script src="{$path}/interface/themes/responsive/js/lib/rater.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jcarousel.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jcarousel.responsive.js"></script>
		<script src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
		<script src="{$path}/ckeditor/ckeditor.js"></script>
		<script type="text/javascript" src="https://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>
		{* Load application specific Javascript *}
		<script src="{$path}/interface/themes/responsive/js/vufind/globals.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/base.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/account.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/browse.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/grouped-work.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/overdrive.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/prospector.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/ratings.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/record.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/responsive.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/results-list.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/searches.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/wikipedia.js"></script>

		<script type="text/javascript">
			{* Override variables as needed *}
			{literal}
			$(document).ready(function(){
				{/literal}
				Globals.path = '{$path}';
				Globals.url = '{$url}';
				Globals.loggedIn = {$loggedIn};
				{if $automaticTimeoutLength}
				Globals.automaticTimeoutLength = {$automaticTimeoutLength};
				{/if}
				{if $automaticTimeoutLengthLoggedOut}
				Globals.automaticTimeoutLengthLoggedOut = {$automaticTimeoutLengthLoggedOut};
				{/if}
				{literal}
			});
			{/literal}
		</script>

		{if $includeAutoLogoutCode == true}
			<script type="text/javascript" src="{$path}/js/autoLogout.js"></script>
		{/if}
		{if $additionalCss}
			<style type="text/css">
				{$additionalCss}
			</style>
		{/if}
	</head>
	<body class="module_{$module} action_{$action}" id="{$module}-{$action}">
		<div class="container">
			{if $showBreadcrumbs}
				<div class="row breadcrumbs">
					<div class="col-sm-9">
						<ul class="breadcrumb">
							<li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"><i class="icon-home"></i> {translate text=$homeLinkText}</a> <span class="divider">&raquo;</span></li>
							{include file="$module/breadcrumbs.tpl"}
						</ul>
					</div>
					<div class="col-sm-3 text-right">
						{if $google_translate_key}
							{literal}
							<div id="google_translate_element">
								<script type="text/javascript">
									function googleTranslateElementInit() {
										new google.translate.TranslateElement({
											pageLanguage: 'en',
											layout: google.translate.TranslateElement.InlineLayout.SIMPLE
											{/literal}
											{if $google_included_languages}
											, includedLanguages: '{$google_included_languages}'
											{/if}
											{literal}
										}, 'google_translate_element');
									}
								</script>
								<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
							</div>
							{/literal}
						{/if}
					</div>
				</div>
			{/if}

			<div id="header-container" class="row">
				{include file='header_responsive.tpl'}
			</div>

			{if false && $showTopSearchBox}
				<div id='searchbar' class="row">
					{include file="Search/searchbox.tpl" showAsBar=true}
				</div>
			{/if}

			<div id="content-container" class="row">
				{if isset($sidebar)}
					{* Setup the left bar *}
					<div class="col-sm-4 col-md-4 col-lg-3" id="side-bar">
						{include file="$sidebar"}
					</div>
					<div class="col-sm-8 col-md-8 col-lg-9" id="main-content-with-sidebar">
						{include file="$module/$pageTemplate"}
					</div>
				{else}
					{include file="$module/$pageTemplate"}
				{/if}
			</div>

			<div id="footer-container" class="row">
				{include file="footer_responsive.tpl"}
			</div>
		</div>

		{include file="modal_dialog.tpl"}


		{include file="tracking.tpl"}

	</body>
</html>