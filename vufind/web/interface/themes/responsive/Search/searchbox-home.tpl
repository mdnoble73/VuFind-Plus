{strip}
<div id="home-page-search" class="row">
	<div class="col-xs-12">
		<div class="row">
			<div class="col-md-12 text-center" id="home-page-search-label">
				SEARCH <span class="glyphicon glyphicon-search"></span>
			</div>
		</div>
		<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-inline" onsubmit="VuFind.Searches.processSearchForm();">
			<div class="row">
				<div class="col-sm-10 col-md-10 col-sm-push-1 col-md-push-1">
					<input type="hidden" name="basicType" id="basicType" value=""/>
					<input type="hidden" name="genealogyType" id="genealogyType" value=""/>
					<fieldset>
						<div class="input-group input-group-sm">
							<input class="form-control"{/strip}
							       id="lookfor"
							       placeholder=""
							       type="search"
							       name="lookfor"
							       size="30"
							       value="{$lookfor|escape:"html"}"
							       title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
							       onkeyup="return VuFind.Searches.resetSearchType()"
							       onclick="$(this).select()"
							       autocomplete="off"
											{strip}/>
							<div class="input-group-btn">
								<button class="btn btn-default" type="submit">GO</button>
								<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
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

									<li class="catalogType">
										<a id="advancedSearch" title="{translate text='Advanced Search'}" onclick="VuFind.Account.ajaxLightbox('{$path}/Search/AdvancedPopup', false)">
											<i class="icon-plus-sign"></i> {translate text="Advanced"}
										</a>
									</li>

									{* Link to Search Tips Help *}
									<li>
										<a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" id="searchTips" class="modalDialogTrigger">
											<i class="icon-question-sign"></i> {translate text='Search Tips'}
										</a>
									</li>
								</ul>
							</div>
						</div>
					</fieldset>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-sm-10 col-md-10 col-sm-push-1 col-md-push-1">
					<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange='VuFind.Searches.enableSearchTypes();' class="form-control">
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
				</div>
			</div>
			<div class="row text-center">
				{if $filterList}
					<label for="keepFiltersSwitch" id="keepFiltersSwitchLabel"><input id="keepFiltersSwitch" type="checkbox" onclick="VuFind.Searches.filterAll(this);" /> Keep Applied Filters</label>
				{/if}
			</div>

			{if $filterList || $hasCheckboxFilters}
				{* Data for searching within existing results *}
				<div id="keepFilters" style="display:none;">
					{foreach from=$filterList item=data key=field}
						{foreach from=$data item=value}
							<input class="existingFilter" type="checkbox" name="filter[]" value='{$value.field}:"{$value.value|escape}"' />
						{/foreach}
					{/foreach}
				</div>
			{/if}
		</form>
	</div>
</div>
{/strip}