{strip}
	{* New Search Box *}
	{include file="Search/searchbox-home.tpl"}

	{* Sort the results*}
	{if $recordCount}
		<div id="results-sort-label" class="row">
			<label for="results-sort">{translate text='Sort Results By'}</label>
		</div>

		<div class="row">
			<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
				{foreach from=$sortList item=sortData key=sortLabel}
					<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
				{/foreach}
			</select>
		</div>
	{/if}

	{* Narrow Results *}
	{if $sideRecommendations}
		<div class="row">
			{foreach from=$sideRecommendations item="recommendations"}
				{include file=$recommendations}
			{/foreach}
		</div>
	{/if}

	<div id="xs-main-content-insertion-point" class="row"></div>
{/strip}