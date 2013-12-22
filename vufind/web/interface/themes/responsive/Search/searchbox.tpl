{strip}
<div class="searchform {if $showAsBar}navbar navbar-static-top{else}well{/if}">
	{if $showAsBar}
	<div class="navbar-inner">
		<div class="container">
	{/if}

	{if $showAsBar}
		<label for="lookfor" class="control-label"><a class="brand" href="#">Search</a></label>
		<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</a>
		<div class="nav-collapse collapse">
	{/if}
	<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-inline {if $showAsBar}navbar-form{else}form-search{/if}" onsubmit="VuFind.Searches.processSearchForm();">
		<input type="hidden" name="basicType" id="basicType" value=""/>
		<input type="hidden" name="genealogyType" id="genealogyType" value=""/>
		<fieldset>
			{if $showAsBar == false}
			<legend>Search the {$librarySystemName} Catalog</legend>
			{/if}

			<div id="search_box_group" class="">
				<input class="input search-query"{/strip}
				       id="lookfor"
				       placeholder="Search for"
				       type="search"
				       name="lookfor"
				       size="30"
				       value="{$lookfor|escape:"html"}"
				       title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
				       onkeyup="return VuFind.Searches.resetSearchType()"
				       onclick="$(this).select()"
							 autocomplete="off"
								{strip}/>

				<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange='VuFind.Searches.enableSearchTypes();'>
					{if $filterList}
						<option data-catalog_type="existing" data-original_type="{$searchSource}" value="existing" title="{translate text="Existing Search"}" selected="selected" id="existing_search_option">{translate text="in Existing Search Results"}</option>
					{/if}
					{/strip}
					{foreach from=$searchSources item=searchOption key=searchKey}
						<option data-catalog_type="{$searchOption.catalogType}"
						        value="{$searchKey}"{if $searchKey == $searchSource && !$filterList} selected="selected"{/if}
										{if $searchKey == $searchSource} id="default_search_type"{/if}
						        title="{$searchOption.description}">
							{translate text="in"} {$searchOption.name}{if $searchOption.external} *{/if}
						</option>
					{/foreach}
					{strip}
				</select>
				<div id="search_button" class="btn-group">
					<button id='searchBarFind' class="btn btn-primary">
						{translate text="Find"}
					</button>
					<button class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
						<span class="caret"></span>
					</button>
					<ul id="searchType" class="dropdown-menu text-left">
						{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
							<li>
								<a class="catalogType" href="#" onclick="return VuFind.Searches.updateSearchTypes('catalog', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
							</li>
						{/foreach}
						<li class="divider catalogType"></li>
						{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
							<li>
								<a class="genealogyType" href="#" onclick="return VuFind.Searches.updateSearchTypes('genealogy', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
							</li>
						{/foreach}
						<li class="divider genealogyType"></li>
						{if $showAdvancedSearchbox == 1}
							<li class="catalogType">
								<a href="{$path}/Search/AdvancedPopup" id="advancedSearch" title="{translate text='Advanced Search'}" class="modalDialogTrigger">
									<i class="icon-plus-sign"></i> {translate text="Advanced"}
								</a>
							</li>
						{/if}

						{* Link to Search Tips Help *}
						<li>
							<a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" id="searchTips" class="modalDialogTrigger">
								<i class="icon-question-sign"></i> {translate text='Search Tips'}
							</a>
						</li>
					</ul>
				</div>
			</div>

			{if $filterList}
				<div class="keepFilters">
					<div style="display:none;">
					{foreach from=$filterList item=data key=field}
						{foreach from=$data item=value}
							<input class="existingFilter" type="checkbox" name="filter[]" value='{$value.field}:"{$value.value|escape}"' />
						{/foreach}
					{/foreach}
					{foreach from=$checkboxFilters item=current}
						{if $current.selected}
							<input class="existingFilter" type="checkbox" name="filter[]" value="{$current.filter|escape}" />
						{/if}
					{/foreach}
					</div>
				</div>
			{/if}
            <img class="brand" src="{img filename="ask_icon_sm.fw.png"}" alt="{$librarySystemName}" title="Help" id="help_undersearch" style="height:20px;" />
		</fieldset>

	</form>
	{if $showAsBar}
				</div>{*nav-collapse*}
			</div>
		</div>
	{/if}
</div>
{/strip}