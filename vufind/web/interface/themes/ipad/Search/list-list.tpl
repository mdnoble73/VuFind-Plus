{js filename="check_item_statuses.js"}
{if $filterList}
<ul class="filters" data-role="listview" data-inset="true" data-dividertheme="e">
	<li data-role="list-divider">{translate text='adv_search_filters'}</li>
	{foreach from=$filterList item=filters key=field name="filterLoop"}
		{foreach from=$filters item=filter}
		<li data-icon="minus"><a data-icon="minus" rel="external" href="{$filter.removalUrl|escape}">
		{if !$smarty.foreach.filterLoop.first}
			{translate text="AND"}
		{/if}
		{translate text=$field}: {$filter.display|escape}</a></li>
		{/foreach}
	{/foreach}
</ul>
{/if}

<ul class="results" data-role="listview" data-split-icon="plus" data-split-theme="c">
	{foreach from=$recordSet item=record name="recordLoop"}
		<li>{* This is raw HTML -- do not escape it: *} {$record}</li>
	{/foreach}
</ul>

<div data-role="controlgroup" data-type="horizontal" align="center">
{if $pageLinks.back} {$pageLinks.back|replace:' href=':' class="prevLink" data-role="button" data-rel="back" href='} {/if} 
{if $pageLinks.next} {$pageLinks.next|replace:' href=':' class="nextLink" rel="external" data-role="button" href='} {/if}
</div>
