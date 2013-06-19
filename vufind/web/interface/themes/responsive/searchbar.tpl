{strip}
<div class="row-fluid">
	<div class="searchform span12">
		{if $searchType == 'advanced'}
			{translate text="Your search"} : "<b>{$lookfor|escape:"html"}</b>"
			<br />
			<a href="{$path}/Search/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
			<a href="{$path}/Search/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
			<a href="{$path}/Search/Home" class="small">{translate text="Start a new Basic Search"}</a>
		{else}
			<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-search form-inline">
				<div>
					<label for="searchSource">Search</label>
					<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange='enableSearchTypes();'>
						{foreach from=$searchSources item=searchOption key=searchKey}
							<option value="{$searchKey}"{if $searchKey == $searchSource} selected="selected"{/if} title="{$searchOption.description}">{if $searchOption.external}* {/if}{$searchOption.name}</option>
						{/foreach}
					</select>
					<label for="lookfor">for</label>
					<input id="lookfor" placeholder="Search term (blank to browse)" type="search" name="lookfor" size="30" value="{$lookfor|escape:"html"}" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."/>
					<label for="basicSearchTypes">by</label>
					<select name="basicType" id="basicSearchTypes" title="Search by Keyword to find subjects, titles, authors, etc. Search by Title or Author for more precise results." {if $searchSource == 'genealogy'}style='display:none'{/if}>
					{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
						<option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
					{/foreach}
					</select>
					<select name="genealogyType" id="genealogySearchTypes" {if $searchSource != 'genealogy'}style='display:none'{/if}>
					{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
						<option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
					{/foreach}
					</select>

				{if $filterList || $hasCheckboxFilters}
					&nbsp;
					<input id="keepFiltersSwitch" type="checkbox" onclick="filterAll(this);" /> <label for="keepFiltersSwitch">{translate text="basic_search_keep_filters"}</label>
					&nbsp;
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
				{/if}

				{*
				<input type="image" name="submit" id='searchBarFind' value="{translate text="Find"}" src="{$path}/interface/themes/marmot/images/find.png" />
				*}
				<div class="btn-group">
					<a href="#" onclick="$('#searchForm').submit();return false;" id='searchBarFind' class="btn btn-primary">{translate text="Find"} <i class="icon-search icon-white"></i></a>
					{if $showAdvancedSearchbox == 1}
						<a href="{$path}/Search/Advanced" id="advancedSearch" class="btn">
							<i class="icon-plus-sign"></i>
						</a>
					{/if}
					{* Link to Search Tips Help *}
					<a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" id="searchTips" class="btn modalDialogTrigger">
						<i class="icon-question-sign"></i>
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

				</div>
			</form>
		{/if}
	</div>
</div>
{/strip}
