{strip}
<div class="searchform {if $showAsBar}navbar navbar-static-top{else}well{/if}">
	{if $showAsBar}
	<div class="navbar-inner">
		<div class="container">
	{/if}

	{if $searchType == 'advanced'}
		{translate text="Your search"} : "<b>{$lookfor|escape:"html"}</b>"
		<br />
		<a href="{$path}/Search/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
		<a href="{$path}/Search/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
		<a href="{$path}" class="small">{translate text="Start a new Basic Search"}</a>
	{else}
		{if $showAsBar}
			<label for="lookfor" class="control-label"><a class="brand" href="#">Search</a></label>
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			<div class="nav-collapse collapse">
		{/if}
		<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-inline {if $showAsBar}navbar-form{else}form-search{/if}">
			<input type="hidden" name="basicType" id="basicType" value=""/>
			<input type="hidden" name="genealogyType" id="genealogyType" value=""/>
			<fieldset>
				{if $showAsBar == false}
				<legend>Search the {$librarySystemName} Catalog</legend>
				{/if}

				<div id="search_box_group" class="">

					<input class="input search-query" id="lookfor" placeholder="Search for" type="search" name="lookfor" size="30" value="{$lookfor|escape:"html"}" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." />

					<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange='VuFind.Searches.enableSearchTypes();'>
						{if $filterList}
							<option data-catalog_type="existing" value="existing" title="{translate text="Existing Search"}">{translate text="in Existing Search Results"}</option>
						{/if}
						{foreach from=$searchSources item=searchOption key=searchKey}
							<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}"{if $searchKey == $searchSource} selected="selected"{/if} title="{$searchOption.description}">{translate text="in"} {$searchOption.name}{if $searchOption.external} *{/if}</option>
						{/foreach}
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
									<a class="catalogType" href="#" onclick="return VuFind.Searches.processSearchForm('catalog', '{$searchVal}', '#searchForm')">{translate text="by"} {translate text=$searchDesc}</a>
								</li>
							{/foreach}
							<li class="divider catalogType"></li>
							{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
								<li>
									<a class="genealogyType" href="#" onclick="return VuFind.Searches.processSearchForm(('genealogy', '{$searchVal}', '#searchForm')">{translate text="by"} {translate text=$searchDesc}</a>
								</li>
							{/foreach}
							<li class="divider genealogyType"></li>
							{if $showAdvancedSearchbox == 1}
								<li>
									<a href="{$path}/Search/Advanced" id="advancedSearch">
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
			</fieldset>
		</form>
	{/if}

	{if $showAsBar}
				</div>{*nav-collapse*}
			</div>
		</div>
	{/if}
</div>
{/strip}