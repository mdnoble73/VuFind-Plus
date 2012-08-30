<div class="searchform">
  {if $searchType == 'advanced'}
    <a href="{$path}/Search/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
    <a href="{$path}/Search/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
    <a href="{$path}" class="small">{translate text="Start a new Basic Search"}</a>
    <br />{translate text="Your search terms"} : "<b>{$lookfor|escape:"html"}</b>"
  {else}
    <form method="get" action="{$path}/Union/Search" id="searchForm" class="search" onsubmit='startSearch();'>
      <div id="searchbar">
      <select name="basicType" id="type">
      {foreach from=$basicSearchTypes item=searchDesc key=searchVal}
        <option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
      {/foreach}
      </select>
      <input id="lookfor" type="text" name="lookfor" size="30" value="{$lookfor|escape:"html"}" />
      <input type="submit" name="submit" id='searchBarFind' value="{translate text="Find"}" />
			{if $filterList || $hasCheckboxFilters}
	    <div class="keepFilters">
	      <input id="retainFiltersCheckbox" type="checkbox" onclick="filterAll(this);" /> {translate text="basic_search_keep_filters"}
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
      </div>
      <div id="shards">
			{if isset($shards)}
				{foreach from=$shards key=shard item=isSelected}
					<input type="checkbox" {if $isSelected}checked="checked" {/if}name="shard[]" value='{$shard|escape}' id="shard{$shard|replace:' ':''|escape}" /> <label for="shard{$shard|replace:' ':''|escape}">{$shard|translate}</label>
				{/foreach}
			{/if}
      </div>
      <div id="searchTools">
          <a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a>
	      {if $showAdvancedSearchbox == 1}
	        <a href="{$path}/Search/Advanced" class="small">{translate text="Advanced Search"}</a>
	      {/if}
	      {if is_array($allLangs) && count($allLangs) > 1}
	         {foreach from=$allLangs key=langCode item=langName}
	           <a class='languageLink {if $userLang == $langCode} selected{/if}' href="{$fullPath|escape}{if $requestHasParams}&amp;{else}?{/if}mylang={$langCode}">{translate text=$langName}</a>
	         {/foreach}
	      {/if}
	      {* Link to Search Tips Help *}
	      <a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">
	        Help <img id='searchHelpIcon' src="{$path}/interface/themes/default/images/help.png" alt="{translate text='Search Tips'}" />
	      </a>
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
</div>
