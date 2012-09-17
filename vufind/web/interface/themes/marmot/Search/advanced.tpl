<div id="page-content" class="content">
	<div id="sidebar">
		{if $searchFilters}
			<div class="sidegroup" id="exploreMore">
				<h4>{translate text="adv_search_filters"}<span>({translate text="adv_search_select_all"} <input type="checkbox" checked="checked" onclick="filterAll(this);" />)</span></h4>
				<div class="sidegroupContents">
				{foreach from=$searchFilters item=data key=field}
				<div>
					<h4>{translate text=$field}</h4>
					<ul>
						{foreach from=$data item=value}
						<li><input type="checkbox" checked="checked" name="filter[]" value='{$value.field|escape}:"{$value.value|escape}"' /> {$value.display|escape}</li>
						{/foreach}
					</ul>
				</div>
				{/foreach}
				</div>
			</div>
		{/if}
		<div class="sidegroup">
			<h4>{translate text="Search Tips"}</h4>
			<div class="sidegroupContents">
				<div class="sideLinksAdv">
					<ul>
						<li><a href="{$url}/Help/Home?topic=search" onclick="window.open('{$url}/Help/Home?topic=advsearch', 'Help', 'width=625, height=510'); return false;">{translate text="Help with Advanced Search"}</a></li>
						<li><a href="{$url}/Help/Home?topic=search" onclick="window.open('{$url}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text="Help with Search Operators"}</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div id="main-content" class="advSearchContent">
		<form method="get" action="{$path}/Search/Results" id="advSearchForm" class="search">
			<div>
				<div class="advSearchContent">
					<div class="resulthead"><h3>{translate text='Advanced Search'}</h3></div>
						{if $editErr}
						{assign var=error value="advSearchError_$editErr"}
						<div class="error">{translate text=$error}</div>
						{/if}
	
						<div id="groupJoin" class="searchGroups">
							<div class="searchGroupDetails">
								{translate text="search_match"} : 
								<select name="join">
									<option value="AND">{translate text="group_AND"}</option>
									<option value="OR"{if $searchDetails}{if $searchDetails.0.join == 'OR'} selected="selected"{/if}{/if}>{translate text="group_OR"}</option>
								</select>
							</div>
							<strong>{translate text="search_groups"}</strong>:
						</div>
	
						{* An empty div. This is the target for the javascript that builds this screen *}
						<div id="searchHolder"></div>
	
						<a href="#" class="add" onclick="addGroup(); return false;">{translate text="add_search_group"}</a>
						<br /><br />
						<input type="submit" name="submit" value="{translate text="Find"}"><br /><br />
					 {if $facetList || $illustratedLimit || $showPublicationDate}
						<h3>{translate text='Limit To'}</h3><br />
						{if $formatCategoryLimit}
							<div class="advancedSearchFacetDetails">
								<div class="advancedSearchFacetHeader">{translate text=$formatCategoryLimit.label}</div>
								<div class="advancedSearchFacetList">
									{foreach from=$formatCategoryLimit item="value" key="display"}
										{if $value.filter != ""}
										<div class="advancedSearchFacetFormatCategory">
											<div><img src="{img filename=$value.imageName}" alt="{translate text=$display}"/></div>
											<div><input type="radio" name="filter[]" value="{$value.filter|escape}"{if $value.selected} checked="checked"{/if} /> <label>{translate text=$display}</label></div>
										</div>
										{/if}
									{/foreach}
								</div>
							</div>
						{/if}
						<table class="citation" width="100%" summary="{translate text='Limit To'}">
							{if $facetList}
								{foreach from=$facetList item="list" key="label"}
								<tr>
									<th align="right">{translate text=$label}: </th>
									<td>
										<select name="filter[]">
											{foreach from=$list item="value" key="display"}
												<option value="{$value.filter|escape}"{if $value.selected} selected="selected"{/if}>{$display|escape}</option>
											{/foreach}
										</select>
									</td>
								</tr>
								{/foreach}
							{/if}
							{if $illustratedLimit}
								<tr>
									<th align="right">{translate text="Illustrated"}: </th>
									<td>
										{foreach from=$illustratedLimit item="current"}
											<input type="radio" name="illustration" value="{$current.value|escape}"{if $current.selected} checked="checked"{/if}> {translate text=$current.text}<br />
										{/foreach}
									</td>
								</tr>
							{/if}
							{if $showPublicationDate}
								<tr>
									<th align="right">{translate text="Publication Year"}: </th>
									<td>
										<label for="yearfrom" class='yearboxlabel'>From:</label>
										<input type="text" size="4" maxlength="4" class="yearbox" name="yearfrom" id="yearfrom" value="" />
										<label for="yearto" class='yearboxlabel'>To:</label>
										<input type="text" size="4" maxlength="4" class="yearbox" name="yearto" id="yearto" value="" />
										
										<div id='yearDefaultLinks'>
										<a onclick="$('#yearfrom').val('2005');$('#yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
										&bull;<a onclick="$('#yearfrom').val('2000');$('#yearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
										&bull;<a onclick="$('#yearfrom').val('1995');$('#yearto').val('');" href='javascript:void(0);'>since&nbsp;1995</a>
										</div>
									</td>
								</tr>
							{/if}
							{if $showLexileScore}
								<tr>
									<th align="right">{translate text="Lexile Score"}: </th>
									<td>
										<div id="lexile-range"></div>
										<label for="lexile_scorefrom" class='yearboxlabel'>From:</label>
										<input type="text" size="4" maxlength="4" class="yearbox" name="lexile_scorefrom" id="lexile_scorefrom" value="" />
										<label for="lexile_scoreto" class='yearboxlabel'>To:</label>
										<input type="text" size="4" maxlength="4" class="yearbox" name="lexile_scoreto" id="lexile_scoreto" value="" />
									</td>
									<script>{literal}
									$(function() {
										$( "#lexile-range" ).slider({
											range: true,
											min: 0,
											max: 2500,
											step: 10,
											values: [ 0, 2500 ],
											slide: function( event, ui ) {
												$( "#lexile_scorefrom" ).val( ui.values[ 0 ] );
												$( "#lexile_scoreto" ).val( ui.values[ 1 ] );
											}
										});
										$( "#lexile_scorefrom" ).change(function (){
											$( "#lexile-range" ).slider( "values", 0, $( "#lexile_scorefrom" ).val());
										});
										$( "#lexile_scoreto" ).change(function (){
											$( "#lexile-range" ).slider( "values", 1, $( "#lexile_scoreto" ).val());
										});
									});{/literal}
									</script>
								</tr>
							{/if}
							</table>
						{/if}
						<input type="submit" name="submit" value="{translate text="Find"}" /><br />
					</div>
			</div>
		</form>
	</div>
</div>

{* Step 1: Define our search arrays so they are usuable in the javascript *}
<script	type="text/javascript">
		var searchFields = new Array();
		{foreach from=$advSearchTypes item=searchDesc key=searchVal}
		searchFields["{$searchVal}"] = "{translate text=$searchDesc}";
		{/foreach}
		var searchJoins = new Array();
		searchJoins["AND"]	= "{translate text="search_AND"}";
		searchJoins["OR"]	 = "{translate text="search_OR"}";
		searchJoins["NOT"]	= "{translate text="search_NOT"}";
		var addSearchString = "{translate text="add_search"}";
		var searchLabel		 = "{translate text="adv_search_label"}";
		var searchFieldLabel = "{translate text="in"}";
		var deleteSearchGroupString = "{translate text="del_search"}";
		var searchMatch		 = "{translate text="search_match"}";
		var searchFormId		= 'advSearchForm';
</script>
{* Step 2: Call the javascript to make use of the above *}
<script	type="text/javascript" src="{$path}/services/Search/advanced.js"></script>
{* Step 3: Build the page *}
<script	type="text/javascript">
	{if $searchDetails}
		{foreach from=$searchDetails item=searchGroup}
			{foreach from=$searchGroup.group item=search name=groupLoop}
				{if $smarty.foreach.groupLoop.iteration == 1}
		var new_group = addGroup('{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}', '{$search.bool}');
				{else}
		addSearch(new_group, '{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}');
				{/if}
			{/foreach}
		{/foreach}
	{else}
		var new_group = addGroup();
		addSearch(new_group);
		addSearch(new_group);
	{/if}
</script>