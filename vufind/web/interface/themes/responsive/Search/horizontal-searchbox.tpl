{strip}
<div id="horizontal-search-box" class="row">
	<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-inline" onsubmit="VuFind.Searches.processSearchForm();">

		{* Hidden Inputs *}
		{if $searchIndex == 'Keyword' || $searchIndex == '' || $searchIndex == 'GenealogyKeyword'}
			<input type="hidden" name="basicType" id="basicType" value="">
			<input type="hidden" name="genealogyType" id="genealogyType" value="">
		{/if}
		<input type="hidden" name="view" id="view" value="{$displayMode}">

		<div class="col-sm-10 col-xs-12">
			<div class="row">
				<div class="col-lg-1 col-md-1 col-sm-2 col-xs-12">
					<label id="horizontal-search-label" for="lookfor" class="">Search for </label>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-10 col-xs-12">
					{* Main Search Term Box *}
					<textarea class="form-control"{/strip}
							          id="lookfor"
							          placeholder="&#128269; SEARCH" {* disabled in css by default. plb 11-19-2014 *}
							          type="search"
							          name="lookfor"
							          {*size="50"*}
							          value=""
							          title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
							          onkeyup="return VuFind.Searches.resetSearchType()"
							          onfocus="$(this).select()"
							          autocomplete="off"
							          rows="1"
											{strip}>
								{$lookfor|escape:"html"}
								</textarea>
				</div>

				{* Search Type *}
				<div class="col-lg-2 col-lg-offset-0 col-md-2 col-md-offset-0 col-sm-3 col-sm-offset-4 col-xs-5 col-xs-offset-0">

					<select name="basicType" class="searchTypeHorizontal form-control catalogType" id="basicSearchTypes" title="Search by Keyword to find subjects, titles, authors, etc. Search by Title or Author for more precise results." {if $searchSource == 'genealogy'}style="display:none"{/if}>
						{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
							<option value="{$searchVal}"{if $basicSearchIndex == $searchVal || $searchIndex == $searchVal} selected="selected"{/if}>by {translate text=$searchDesc}</option>
						{/foreach}
					</select>
					{*TODO: How to chose the Genealogy Search type initially *}
					<select name="genealogyType" class="searchTypeHorizontal form-control genealogyType" id="genealogySearchTypes" {if $searchSource != 'genealogy'}style="display:none"{/if}>
						{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
							<option value="{$searchVal}"{if $genealogySearchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
						{/foreach}
					</select>

				</div>

				{* TODO: No column if the input is hidden; enlarge search term column *}
				<div class="col-lg-3 col-md-3 col-sm-5 col-xs-7">
					{if $searchSources|@count == 1}
						<input type="hidden" name="searchSource" value="{$searchSource}">
					{else}
						<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange="VuFind.Searches.enableSearchTypes();" class="searchSourceHorizontal form-control">
							{foreach from=$searchSources item=searchOption key=searchKey}
								<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}"
												{if $searchKey == $searchSource && !$filterList} selected="selected"{/if}
												{if $searchKey == $searchSource} id="default_search_type"{/if}
												title="{$searchOption.description}">
									{translate text="in"} {$searchOption.name}{if $searchOption.external} *{/if}
								</option>
							{/foreach}
						</select>
					{/if}

				</div>

			</div>
		</div>

		{* GO Button & Search Links*}
		<div id="horizontal-search-button-container" class="col-sm-2 col-xs-12">

			<button class="btn btn-default" type="submit">
				<span class="glyphicon glyphicon-search"></span>
			</button>

			{* Return to Advanced Search Link *}
			{if $searchType == 'advanced'}
				<div style="display: inline-block">
					&nbsp;
					<a id="advancedSearchLink" href="{$path}/Search/Advanced">{translate text='Edit This Advanced Search'}</a>
				</div>

			{* Show Advanced Search Link *}
			{elseif $showAdvancedSearchbox}
				<div style="display: inline-block">
					&nbsp;
					<a id="advancedSearchLink" href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a>
				</div>
			{/if}

		</div>

		</form>
</div>
{/strip}