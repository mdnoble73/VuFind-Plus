{strip}
	<div id="scrollerTitle{$listName}{$key}" class="scrollerTitle">
		<span class="scrollerTextOnlyListNumber">{$key}) </span>
		<a onclick="trackEvent('ListWidget', 'Title Click', '{$listName}')" href="{$titleURL}" id="descriptionTrigger{$shortId}">
			<span class="scrollerTextOnlyListTitle">{$title}</span>
		</a>
		<span class="scrollerTextOnlyListBySpan"> by </span>
		<a onclick="trackEvent('ListWidget', 'Title Click', '{$listName}')" href="{$titleURL}" id="descriptionTrigger{$shortId}">
			<span class="scrollerTextOnlyListAuthor">{$author}</span>
		</a>
		{* show ratings check in the template *}
		{*{include file="GroupedWork/title-rating.tpl" showNotInterested=false}*}
	</div>
	{*<div id="descriptionPlaceholder{$id}" style="display:none" class="loaded">*}
		{*{include file="Record/ajax-description-popup.tpl"}*}
	{*</div>*}
{/strip}

