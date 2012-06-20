<div class="searchform">
  {if $searchType == 'advanced'}
    {translate text="Your search"} : "<b>{$lookfor|escape:"html"}</b>"
    <br />
    <a href="{$path}/Search/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
    <a href="{$path}/Search/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
    <a href="{$path}" class="small">{translate text="Start a new Basic Search"}</a>
  {else}
    <form method="get" action="{$path}/Union/Search" id="searchForm" class="search">
    	<div>
      Search
	    <select name="searchSource" id="searchSource" title="Select what to search.  Items marked with a * will redirect you to one of our partner sites." onchange='enableSearchTypes();'>
	      {foreach from=$searchSources item=searchOption key=searchKey}
          <option value="{$searchKey}" {if $searchKey == $searchSource}selected="selected"{/if} title="{$searchOption.description}">{if $searchOption.external}* {/if}{$searchOption.name}</option>
        {/foreach}
      </select>
      for
      <input id="lookfor" type="text" name="lookfor" size="30" value="{$lookfor|escape:"html"}" title="Enter one or more terms to search for.  Surrounding a term with quotes will limit result to only those that exactly match the term." />
      by
      <select name="basicType" id="basicSearchTypes" {if $searchSource == 'genealogy'}style='display:none'{/if}>
      {foreach from=$basicSearchTypes item=searchDesc key=searchVal}
        <option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
      {/foreach}
      </select>
      <select name="genealogyType" id="genealogySearchTypes" {if $searchSource != 'genealogy'}style='display:none'{/if}>
      {foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
        <option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
      {/foreach}
      </select>
      
      <input type="image" name="submit" id='searchBarFind' value="{translate text="Find"}" src="{$path}/interface/themes/marmot/images/find.png" />
      {if $showAdvancedSearchbox == 1}
        <a href="{$path}/Search/Advanced" class="small">{translate text="Advanced"}</a>
      {/if}
      {* Link to Search Tips Help *}
      <a href="{$url}/Help/Home?topic=search" title="{translate text='Search Tips'}" onclick="window.open('{$url}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">
        <img src="{$url}/images/silk/help.png" alt="{translate text='Search Tips'}" />
      </a>
      
      {* Do we have any checkbox filters? *}
      {assign var="hasCheckboxFilters" value="0"}
      {if isset($checkboxFilters) && count($checkboxFilters) > 0}
        {foreach from=$checkboxFilters item=current}
          {if $current.selected}
            {assign var="hasCheckboxFilters" value="1"}
          {/if}
        {/foreach}
      {/if}
      {if $filterList || $hasCheckboxFilters}
	      <div class="keepFilters">
	        <input type="checkbox" onclick="filterAll(this);" /> {translate text="basic_search_keep_filters"}
	        <div style="display:none;">
	        {foreach from=$filterList item=data key=field}
		        {foreach from=$data item=value}
		          <input type="checkbox" name="filter[]" value='{$value.field}:"{$value.value|escape}"' />
		        {/foreach}
	        {/foreach}
	        {foreach from=$checkboxFilters item=current}
            {if $current.selected}
              <input type="checkbox" name="filter[]" value="{$current.filter|escape}" />
            {/if}
          {/foreach}
	        </div>
	      </div>
      {/if}
      <div id="shards" style="display:none">
			{if isset($shards)}
				{foreach from=$shards key=shard item=isSelected}
					{*
					<input type="checkbox" {if $isSelected}checked="checked" {/if}name="shard[]" value='{$shard|escape}' id="shard{$shard|replace:' ':''|escape}" /> <label for="shard{$shard|replace:' ':''|escape}">{$shard|translate}</label>
					*}
					{*
					<input type="checkbox" checked="checked" name="shard[]" value='{$shard|escape}' id="shard{$shard|replace:' ':''|escape}" /> <label for="shard{$shard|replace:' ':''|escape}">{$shard|translate}</label>
					*}
				{/foreach}
			{/if}
      </div>
      </div>
    </form>
  {/if}
</div>
