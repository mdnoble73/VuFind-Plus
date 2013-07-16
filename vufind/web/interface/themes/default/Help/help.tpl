<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang}" xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{translate text="MyResearch Help"}</title>
		{css media="screen" filename="help.css"}
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	</head>
	<body>
		{if $warning}
			<p class="warning">
				{translate text='Sorry, but the help you requested is unavailable in your language.'}
			</p>
		{/if}
		{include file="$pageTemplate"}
	</body>
</html>
