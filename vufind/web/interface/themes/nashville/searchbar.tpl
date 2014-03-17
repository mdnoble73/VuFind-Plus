{strip}


<div class="searchform">
		<form method="get" action="{$path}/Union/Search" id="searchForm" class="search">
			<div>
{*
			<input id="lookfor" placeholder="Search Keyword / Title / Author" type="search" name="lookfor" size="30" value="{$lookfor|escape:"html"}" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."/>
*}
{* 20140315 James: bad hack to staunch frequent solr errors on mixing basic and advanced search terms : should be replaced next week *}
			<input id="lookfor" placeholder="Search Keyword / Title / Author" type="search" name="lookfor" size="30" 
				value="{$lookfor|escape:"html"|regex_replace:"/\\\\/":""|regex_replace:"/^AND/":"and"|regex_replace:"/(All Fields:|Author:|CallNumber:|ISBN\/ISSN\/UPC:|Keyword:|Publisher:|Series:|Subject:|Table of Contents:|Title:|Year of Publication:)/":""}" 
				onblur="this.value=this.value.replace(/\\/g,''); this.value=this.value.replace(/^AND/,'and');" 
				title="Enter one or more terms to search for. Surrounding a term with quotes will limit result to only those that exactly match the term."
			/>
			&nbsp;by&nbsp;
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

			<input type="submit" name="submit" id='searchBarFind' value="{translate text="Find"}" />
			{if $showAdvancedSearchbox == 1}
				&nbsp;<a href="{$path}/Search/Advanced" class="small">{translate text="Advanced"}</a>
			{/if}
			{* Link to Search Tips Help *}
			&nbsp;
			<a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">
				<span class="silk help">&nbsp;</span>
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
			
			</div>
		</form>
</div>
{/strip}
