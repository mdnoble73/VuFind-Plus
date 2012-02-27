<div class="searchform">
  {if $searchType == 'AuthorityAdvanced'}
    <a href="{$path}/Authority/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
    <a href="{$path}/Authority/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
    <a href="{$path}/Authority/Home" class="small">{translate text="Start a new Basic Search"}</a>
    <br>{translate text="Your search terms"} : "<b>{$lookfor|escape:"html"}</b>"
  {else}
    <form method="GET" action="{$path}/Authority/Search" name="searchForm" id="searchForm" class="search">
      <div class="hiddenLabel"><label for="lookfor">{translate text="Search For"}:</label></div>
      <input type="text" id="lookfor" name="lookfor" size="30" value="{$lookfor|escape:"html"}">
      <div class="hiddenLabel"><label for="type">{translate text="in"}:</label></div>
      <select id="type" name="type">
        {foreach from=$authSearchTypes item=searchDesc key=searchVal}
          <option value="{$searchVal}"{if $searchIndex == $searchVal} selected{/if}>{translate text=$searchDesc}</option>
        {/foreach}
      </select>
      <input type="submit" name="submit" value="{translate text="Find"}">
      {* Not yet supported: <a href="{$path}/Authority/Advanced" class="small">{translate text="Advanced"}</a> *}
      {if $filterList}
        <div class="keepFilters">
          <input id="retainAll" type="checkbox" {if $retainFiltersByDefault}checked="checked" {/if}onclick="filterAll(this);" />
          <label for="retainAll">{translate text="basic_search_keep_filters"}</label>
          <div style="display:none;">
          {foreach from=$filterList item=data key=field}
          {foreach from=$data item=value}
            <input type="checkbox" {if $retainFiltersByDefault}checked="checked" {/if} name="filter[]" value='{$value.field|escape}:&quot;{$value.value|escape}&quot;' />
          {/foreach}
          {/foreach}
          </div>
        </div>
      {/if}
    </form>
  {/if}
</div>