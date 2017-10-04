{strip}
	<meta property="og:url" content="{$path}/GroupedWork/{$id}">
	<meta property="og:site_name" content="{$librarySystemName} Pika Catalog">

	<meta property="title" content="{$recordDriver->getTitle()|removeTrailingPunctuation|escape}">
	<meta property="og:title" content="{$recordDriver->getTitle()|removeTrailingPunctuation|escape}">
	<meta property="og:type" content="{$recordDriver->getFormats()|escape}">
	<meta property="description" content="{$description|strip_tags|escape}">
	<meta property="og:description" content="{$description|strip_tags|escape}">
	<meta property="og:image" content="{$recordDriver->getBookcoverUrl('medium')}">
{/strip}