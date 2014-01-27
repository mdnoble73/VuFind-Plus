{strip}
	<div class="col-xs-6 col-sm-4 col-md-3 col-lg-2 text-center">
		<div class="thumbnail">
			<a href="{$path}/GroupedWork/{$summId}/Home">
				<img class="hidden-xs hidden-sm visible-md" src="{$bookCoverUrlMedium}">
				<img class="visible-xs visible-sm hidden-md hidden-lg" src="{$bookCoverUrl}">
			</a>
			{include file="GroupedWork/title-rating.tpl" id=$summId showNotInterested=false}
		</div>
	</div>
{* Insert separators at the appropriate locations *}
	{if $recordIndex % 6 == 0}
		<div class="clearfix visible-lg"></div>
	{elseif $recordIndex % 4 == 0}
		<div class="clearfix visible-md"></div>
	{elseif $recordIndex % 3 == 0}
		<div class="clearfix visible-sm"></div>
	{/if}
{/strip}