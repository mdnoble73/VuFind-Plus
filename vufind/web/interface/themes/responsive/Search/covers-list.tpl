{strip}
	<div class="results-covers home-page-browse-thumbnails">
		{*<form id="addForm" action="{$path}/MyAccount/HoldMultiple" class="">*}
		{* multiple hold form taken from list-list.tpl. Needed? *}
		{foreach from=$recordSet item=record name="recordLoop"}
			{*<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">*}
				{* This is raw HTML -- do not escape it: *}
				{$record}
			{*</div>*}
			{foreachelse}
				{include file="Browse/noResults.tpl"}
		{/foreach}
		{*</form>*}
	</div>
{/strip}