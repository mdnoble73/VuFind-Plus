{strip}
<div id="filtersContainer">
	<h3>Filters</h3>
	<div id="filters">
	{foreach from=$filters item=filter}
		<div class="reportFilter">
			<div class="filterLabel">{$filter.label}</div>
			{foreach from=$filter.values item=label key=value}
				<input type="checkbox" value="{$filter.field}_{$value}" class="{$filter.field}" id="{$filter.field}_{$value}" /><label for="{$filter.field}_{$value}">{$label}</label>
				&nbsp;
			{/foreach}
		</div>
	{/foreach}
	</div>
</div>
{/strip}