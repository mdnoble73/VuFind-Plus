<div id="search"><div id="search-inner">
{if $searchType == 'advanced'}
  <a href="{$path}/Search/Advanced?edit={$searchId}">{translate text="Edit this Advanced Search"}</a> |
  <a href="{$path}/Search/Advanced">{translate text="Start a new Advanced Search"}</a> |
  <a href="{$url}" class="small">{translate text="Start a new Basic Search"}</a>
  <br />{translate text="Your search terms"} : "<b>{$lookfor|escape:"html"}</b>"
{else}
  <form method="get" action="{$path}/Union/Search" id="searchForm" class="search" onsubmit='startSearch();'>
    <label id="type-label" for="basicType">{translate text='Search'}</label>
    <select name="basicType" id="basicType">
    {foreach from=$basicSearchTypes item=searchDesc key=searchVal}
      <option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
    {/foreach}
    </select>
    <div id="search-input-wrapper">
      <div id="search-input" class="clearfix">
      <input id="lookfor" type="text" name="lookfor" size="30" value="{$lookfor|escape:"html"}" />
      <input id="lookfor-submit" type="submit" name="submit" value="{translate text='Go'}" />
      </div>
      <div id="shards">
        <ul class="inline right"><li><a href="{$path}/Search/Advanced" class="small">{translate text="Advanced Search"}</a></li></ul>
        {if isset($shards)}
          {foreach from=$shards key=shard item=isSelected}
            <input type="checkbox" {if $isSelected}checked="checked" {/if}name="shard[]" value='{$shard|escape}' id="shard-{$shard|replace:' ':''|escape}" /> <label for="shard-{$shard|replace:' ':''|escape}">{$shard|translate}</label>
          {/foreach}
        {/if}
        {if $filterList || $hasCheckboxFilters}
        <div>
          <input id="retainFiltersCheckbox" type="checkbox" onclick="filterAll(this);" /> <label for="retainFiltersCheckbox">{translate text="basic_search_keep_filters"}</label>
          <div style="display: none;">
            <ul class="inline left">
            {foreach from=$filterList item=data key=field}
              {foreach from=$data item=value}
                <li><input type="checkbox" name="filter[]" value='{$value.field}:"{$value.value|escape}"' /> {$field}: {$value.value|escape}</li>
              {/foreach}
            {/foreach}
            {foreach from=$checkboxFilters item=current}
              {if $current.selected}
                <li><input type="checkbox" name="filter[]" value="{$current.filter|escape}" /> {$current.filter|escape}</li>
              {/if}
            {/foreach}
            </ul>
          </div>
        </div>
        {/if}
      </div>
    </div>

    {* Do we have any checkbox filters? *}
    {assign var="hasCheckboxFilters" value="0"}
    {if isset($checkboxFilters) && count($checkboxFilters) > 0}
      {foreach from=$checkboxFilters item=current}
        {if $current.selected}
          {assign var="hasCheckboxFilters" value="1"}
        {/if}
      {/foreach}
    {/if}
  </form>

  {if false && strlen($lookfor) > 0 && count($repeatSearchOptions) > 0}
  <div class='repeatSearchBox'>
    <label for='repeatSearchIn'>Repeat Search In: </label>
    <select name="repeatSearchIn" id="repeatSearchIn">
      {foreach from=$repeatSearchOptions item=repeatSearchOption}
        <option value="{$repeatSearchOption.link}">{$repeatSearchOption.name}</option>
      {/foreach}
    </select>
    <input type="button" name="repeatSearch" value="{translate text="Go"}" onclick="window.open(document.getElementById('repeatSearchIn').options[document.getElementById('repeatSearchIn').selectedIndex].value)">
  </div>
  {/if}
{/if}
</div></div>