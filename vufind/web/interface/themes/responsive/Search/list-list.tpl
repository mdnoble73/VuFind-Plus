{strip}
	<div>
		{foreach from=$recordSet item=record name="recordLoop"}
			<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
				{* This is raw HTML -- do not escape it: *}
				{$record}
				{if $smarty.foreach.recordLoop.iteration == 2 && $exploreMoreOptions}
					{include file="Search/explore-more-bar.tpl"}
				{/if}
			</div>
		{/foreach}
	</div>
{/strip}

