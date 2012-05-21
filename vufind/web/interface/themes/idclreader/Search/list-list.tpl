<script type="text/javascript">
$(document).ready(function() {literal} { {/literal}
  doGetStatusSummaries();
  //doGetRatings();
  {if $user}
  	doGetSaveStatuses();
  {/if}
{literal} }); {/literal}
</script>
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

<ul id='resultSearch' data-role="listview" >
	{if $pageLinks.back}
		<li data-iconpos='left' data-icon='arrow-l'>
			{$pageLinks.back|replace:'&laquo; Prev':'PREV'}
		</li>
	{/if}
	{foreach from=$recordSet item=record name="recordLoop"}
		<li>{* This is raw HTML -- do not escape it: *} {$record}</li>
	{/foreach}
	{if $pageLinks.next}
		<li>
			{$pageLinks.next|replace:'Next &raquo;':'NEXT'}
		</li>
	{/if}
</ul>