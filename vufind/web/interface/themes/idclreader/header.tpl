<!DOCTYPE html>
<html>
	<head>
		<title>{$site.title|escape}</title>
		
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
	
		{css filename="jquery.mobile-1.1.0.min.css"}
		{css filename="skin.css"}
		{css filename="idclreader.css"}
		
		{js filename="jquery-1.7.1.min.js"}
		{js filename="jquery.mobile-1.1.0.min.js"}
		{js filename="jquery.jcarousel.js"}
		{js filename="jquery.touchwipe.1.1.1.js"}
		
		{literal}
			<script type="text/javascript">
				var path = '{$path}';
				var loggedIn = {/literal}{if $user}true{else}false{/if};{literal}
		    </script>
		{/literal}
	</head>
	<body>
<div data-role="page" data-theme="a" >
	<div data-role="header"></div> 
	<div data-role="content">