{strip}
	{* New Search Box *}
	{include file="Search/searchbox-home.tpl"}

	{* Sort the results*}
	{if $recordCount}
		<div id="results-sort-label">
			<label for="results-sort">{translate text='Sort Results By'}</label>
		</div>

		<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
			{foreach from=$sortList item=sortData key=sortLabel}
				<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
			{/foreach}
		</select>
	{/if}

{* Narrow Results *}
	{if $sideRecommendations}
		{foreach from=$sideRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}
{/strip}