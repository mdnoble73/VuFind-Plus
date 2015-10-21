{strip}{literal}
	<style>
		/* Advanced search screen stuff */
		.advSearchContent {
			/* Needed for IE7 compatibility: */
			width: 99%;
		}
		.groupSearchHolder {
			padding: 10px 10px 5px;
		}
		.groupSearchHolder .advRow {
			padding: 1px 0px;
		}
		.groupSearchHolder .advRow div {
			float: left;
			padding: 0px 2px;
		}
		.clearer {
			display: block;
			clear: both;
			height: 2px;
		}
		.groupSearchHolder span.clearer {
			display: block;
			clear: left;
		}
		.groupSearchDetails .join {
			padding-right: 5px;
			float: left;
		}
		/*.groupSearchHolder .searchLabel, .groupSearchHolder .join {*/
			/*width: 75px;*/
			/*text-align: right;*/
		/*}*/
		.searchLabel {
			width: 90px;
			font-weight: bold;
			text-align: right;
		}
		.addSearch {
			padding: 0 0 4px 102px;
		}
		.groupSearchHolder .terms {
			width: 220px;
		}
		.groupSearchHolder .terms input {
			width: 100%;
		}
		.groupSearchHolder .field {
		}
		.group .groupSearchDetails {
			float: right;
			text-align: right;
			padding: 3px 5px;
		}
		/*.group0 .groupSearchDetails {*/
			/*border: 1px solid #D8D7D8;*/
			/*border-top: 0px;*/
		/*}*/
		/*.group1 .groupSearchDetails {*/
			/*border: 1px solid #94C632;*/
			/*border-top: 0px;*/
		/*}*/
		#searchHolder .group0 {
			/*border-top : 1px solid #D8D7D8;*/
			/*background:url(/images/gradient_grey.gif) repeat-y;*/
		}
		#searchHolder .group1 {
			/*border-top: 1px solid #94C632;*/
			/*background:url(/images/gradient_green.gif) repeat-y;*/
		}
		#searchHolder .group {
			margin-bottom: 10px;
		}
		.searchGroups {
			margin-bottom: 10px;
			padding: 2px 5px;
			font-size: 125%;
		}
		.searchGroups .searchGroupDetails {
			float: right;
		}
		.search .filterList h3, .search .filterList h4 {
			font-weight: bold;
		}
		.search .filterList h3 span {
			font-weight: normal;
			font-size: 80%;
		}
		.search .filterList input {
			vertical-align: middle;
		}
		.search .filterList ul {
			margin-bottom: 5px;
		}
		.keepFilters input {
			vertical-align: middle;
		}
		#facetTable {
			width: auto;
			margin-left: auto;
			margin-right: auto;
		}
		.facetsTop td, .facetsTop th {
			padding-right: 15px;
		}
		.facetsTop th {
			font-weight: bold;
		}
		.facetsTop th span {
			font-weight: normal;
		}
	</style>{/literal}
<div id="page-content" class="content">
	<div id="sidebar">
		{*TODO Works, Verify this is wanted. *}
		{if $searchFilters}
			<div class="sidegroup" id="exploreMore">
				<h4>{translate text="adv_search_filters"}<span>({translate text="adv_search_select_all"} <input type="checkbox" checked="checked" onclick="filterAll(this);">)</span></h4>
				<div class="sidegroupContents">
					{foreach from=$searchFilters item=data key=field}
						<div>
							<h4>{translate text=$field}</h4>
							<ul>
								{foreach from=$data item=value}
									<li><input type="checkbox" checked="checked" name="filter[]" value='{$value.field|escape}:"{$value.value|escape}"'> {$value.display|escape}</li>
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
						<div class="alert alert-warning">{translate text=$error}</div>
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

					<a href="#" class="btn btn-default" onclick="addGroup(); return false;"><span class="glyphicon glyphicon-plus"></span>&nbsp;{translate text="add_search_group"}</a>
					<br><br>
					<input type="submit" name="submit" value="{translate text="Find"}" class="btn btn-primary pull-right"><br><br>
					{if $facetList || $illustratedLimit || $showPublicationDate}
						<h3>{translate text='Limit To'} : </h3><br>
						{if $formatCategoryLimit}
							<div class="advancedSearchFacetDetails">
								<div class="advancedSearchFacetHeader">{translate text=$formatCategoryLimit.label}</div>
								<div class="advancedSearchFacetList">
									{foreach from=$formatCategoryLimit item="value" key="display"}
										{if $value.filter != ""}
											<div class="advancedSearchFacetFormatCategory">
												<div><input id="categoryValue_{$display|lower|replace:' ':''}" type="radio" name="filter[]" value="{$value.filter|escape}"{if $value.selected} checked="checked"{/if}> <label for="categoryValue_{$display|lower|replace:' ':''}"><span class="categoryValue categoryValue_{$display|lower|replace:' ':''}">{translate text=$display}</span></label></div>
											</div>
										{/if}
									{/foreach}
								</div>
							</div>
						{/if}
						<table id="facetTable" class="table table-bordered" summary="{translate text='Limit To'}">
							{if $facetList}
								{foreach from=$facetList item="facetInfo" key="label"}
									<tr>
										<th align="right">{translate text=$label}: </th>
										<td>
											{if $facetInfo.facetName == "publishDate"}
												<label for="yearfrom" class='yearboxlabel'>From:</label>
												<input type="text" size="4" maxlength="4" class="yearbox" name="yearfrom" id="yearfrom" value="">
												<label for="yearto" class='yearboxlabel'>To:</label>
												<input type="text" size="4" maxlength="4" class="yearbox" name="yearto" id="yearto" value="">

												<div id='yearDefaultLinks'>
													<a onclick="$('#yearfrom').val('2015');$('#yearto').val('');" href='javascript:void(0);'>since&nbsp;2015</a>
													&bull;<a onclick="$('#yearfrom').val('2010');$('#yearto').val('');" href='javascript:void(0);'>since&nbsp;2010</a>
													&bull;<a onclick="$('#yearfrom').val('2005');$('#yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
												</div>
											{elseif $facetInfo.facetName == "lexile_score"}
												<div id="lexile-range"></div>
												<label for="lexile_scorefrom" class='yearboxlabel'>From:</label>
												<input type="text" size="4" maxlength="4" class="yearbox" name="lexile_scorefrom" id="lexile_scorefrom" value="">
												<label for="lexile_scoreto" class='yearboxlabel'>To:</label>
												<input type="text" size="4" maxlength="4" class="yearbox" name="lexile_scoreto" id="lexile_scoreto" value="">
												<script type="text/javascript">{literal}
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
											{else}
												<select name="filter[]">
													{foreach from=$facetInfo.values item="value" key="display"}
														{if strlen($display) > 0}
															<option value="{$value.filter|escape}"{if $value.selected} selected="selected"{/if}>{$display|escape|truncate:80}</option>
														{/if}
													{/foreach}
												</select>
											{/if}
										</td>
									</tr>
								{/foreach}
							{/if}
							{if $illustratedLimit}
								<tr>
									<th align="right">{translate text="Illustrated"}: </th>
									<td>
										{foreach from=$illustratedLimit item="current"}
											<input type="radio" name="illustration" value="{$current.value|escape}"{if $current.selected} checked="checked"{/if}> {translate text=$current.text}<br>
										{/foreach}
									</td>
								</tr>
							{/if}

						</table>
						<input type="submit" name="submit" value="{translate text="Find"}" class="btn btn-primary pull-right"><br>
					{/if}
				</div>
			</div>
		</form>
	</div>
</div>
{/strip}

{* Step 1: Define our search arrays so they are usuable in the javascript *}
<script	type="text/javascript">
	var searchFields = new Array();
	{foreach from=$advSearchTypes item=searchDesc key=searchVal}
	searchFields["{$searchVal}"] = "{translate text=$searchDesc}";
	{/foreach}
	var searchJoins = new Array();
	searchJoins["AND"]  = "{translate text="search_AND"}";
	searchJoins["OR"]   = "{translate text="search_OR"}";
	searchJoins["NOT"]  = "{translate text="search_NOT"}";
	var addSearchString = "{translate text="add_search"}";
	var searchLabel     = "{translate text="adv_search_label"}";
	var searchFieldLabel = "{translate text="in"}";
	var deleteSearchGroupString = "{translate text="del_search"}";
	var searchMatch     = "{translate text="search_match"}";
	var searchFormId    = 'advSearchForm';
</script>
{* Step 2: Call the javascript to make use of the above *}
<script	type="text/javascript" src="{$path}/services/Search/advanced.min.js"></script>
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
