<form method="get" action="{$path}/Union/Search" data-ajax="false">
	<div data-role="fieldcontain">
		<label class="offscreen" for="searchForm_lookfor">
				{translate text="Search"}
		</label>
		<input type="search" placeholder="{translate text='Search'}" name="lookfor" id="searchForm_lookfor" value="{$lookfor|escape}"/>
		<label class="offscreen" for="searchSource">{translate text="Source"}</label>
		<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange='enableSearchTypes();'>
			{foreach from=$searchSources item=searchOption key=searchKey}
				<option value="{$searchKey}" {if $searchKey == $searchSource}selected="selected"{/if} title="{$searchOption.description}">{if $searchOption.external}* {/if}{$searchOption.name}</option>
			{/foreach}
		</select>
		
		<label class="offscreen" for="searchForm_type">{translate text="Search Type"}</label>
		<select id="searchForm_type" name="basicType" data-native-menu="false">
			{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
				<option value="{$searchVal}"{if $searchType == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
			{/foreach}
		</select>
	</div>
	<div data-role="fieldcontain">
		<input type="submit" name="submit" value="{translate text="Find"}"/>
	</div>
	{if $lastSort}<input type="hidden" name="sort" value="{$lastSort|escape}" />{/if}
</form>
