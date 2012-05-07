<!DOCTYPE html>
<html>
	<head>
		<title>{$site.title|escape}</title>
		
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
	
		{css filename="jquery.mobile-1.1.0.min.css"}
		{css filename="skin.css"}
		{css filename="idclreader.css"}
		{css filename="idclreader-icons.css"}
		{css filename="formats.css"}
		
		{literal}
			<script type="text/javascript">
				var path = '{/literal}{$path}{literal}';
				var loggedIn = {/literal}{if $user}true{else}false{/if};{literal}
				var url = '{/literal}{$url}{literal}';
		    </script>
		{/literal}
		
		{js filename="jquery-1.7.1.min.js"}
		{js filename="custom.js"}
		{js filename="jquery.mobile-1.1.0.min.js"}
		{js filename="jquery.jcarousel.js"}
		{js filename="jquery.touchwipe.1.1.1.js"}
		<script type="text/javascript" src="{$path}/js/scripts.js"></script>
		<script type="text/javascript" src="{$path}/services/Search/ajax.js"></script>

	</head>
	<body>
<div data-role="page" data-theme="a">
	<div data-role="header" data-theme="a">
		{if $ButtonBack}
			<a href="#" data-icon="back" data-rel="back">Back</a>
		{/if}
		{if $MobileTitle neq ""} 
			<h1>{$MobileTitle}</h1>
		{/if}	
		{if $ButtonHome}
			<a href="/"  data-icon="home">Home</a>
		{/if}
	</div> 
	<div data-role="content" data-theme="a" id='idclReaderContent'>