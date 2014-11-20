<div id="main-content" class="advSearchContent form-horizontal">
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

				<a href="#" onclick="addGroup(); return false;"><span class="silk add">&nbsp;</span>{translate text="add_search_group"}</a>
				<br /><br />
				<input type="submit" name="submit" value="{translate text="Find"}" /><br /><br />
				{if $facetList || $illustratedLimit || $showPublicationDate}
					<h3>{translate text='Limit To'}</h3><br />
					{if $formatCategoryLimit}
						<div class="advancedSearchFacetDetails">
							<div class="advancedSearchFacetHeader">{translate text=$formatCategoryLimit.label}</div>
							<div class="advancedSearchFacetList">
								{foreach from=$formatCategoryLimit item="value" key="display"}
									{if $value.filter != ""}
										<div class="advancedSearchFacetFormatCategory">
											<div><input id="categoryValue_{$display|lower|replace:' ':''}" type="radio" name="filter[]" value="{$value.filter|escape}"{if $value.selected} checked="checked"{/if} /> <label for="categoryValue_{$display|lower|replace:' ':''}"><span class="categoryValue categoryValue_{$display|lower|replace:' ':''}">{translate text=$display}</span></label></div>
										</div>
									{/if}
								{/foreach}
							</div>
						</div>
					{/if}
					<table class="citation" width="100%" summary="{translate text='Limit To'}">
						{if $facetList}
							{foreach from=$facetList item="facetInfo" key="label"}
								<tr>
									<th align="right">{translate text=$label}: </th>
									<td>
										{if $facetInfo.facetName == "publishDate"}
											<label for="publishDateyearfrom" class='yearboxlabel'>From:</label>
											<input type="text" size="4" maxlength="4" class="yearbox" name="publishDateyearfrom" id="publishDateyearfrom" value="" />
											<label for="publishDateyearto" class='yearboxlabel'>To:</label>
											<input type="text" size="4" maxlength="4" class="yearbox" name="publishDateyearto" id="publishDateyearto" value="" />

											<div id='yearDefaultLinks'>
												<a onclick="$('#publishDateyearfrom').val('2005');$('#publishDateyearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
												&bull;<a onclick="$('#publishDateyearfrom').val('2000');$('#publishDateyearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
												&bull;<a onclick="$('#publishDateyearfrom').val('1995');$('#publishDateyearto').val('');" href='javascript:void(0);'>since&nbsp;1995</a>
											</div>
										{elseif $facetInfo.facetName == "lexile_score"}
											<div id="lexile-range"></div>
											<label for="lexile_scorefrom" class='yearboxlabel'>From:</label>
											<input type="text" size="4" maxlength="4" class="yearbox" name="lexile_scorefrom" id="lexile_scorefrom" value="" />
											<label for="lexile_scoreto" class='yearboxlabel'>To:</label>
											<input type="text" size="4" maxlength="4" class="yearbox" name="lexile_scoreto" id="lexile_scoreto" value="" />
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
										<input type="radio" name="illustration" value="{$current.value|escape}"{if $current.selected} checked="checked"{/if}> {translate text=$current.text}<br />
									{/foreach}
								</td>
							</tr>
						{/if}

					</table>
				{/if}
				<input type="submit" name="submit" value="{translate text="Find"}" /><br />
			</div>
		</div>
	</form>
</div>
{* Step 1: Define our search arrays so they are usuable in the javascript *}
<script type="text/javascript">
    var searchFields = new Array();
    {foreach from=$advSearchTypes item=searchDesc key=searchVal}
    searchFields["{$searchVal}"] = "{translate text=$searchDesc}";
    {/foreach}
    var searchJoins = new Array();
    searchJoins["AND"]  = "{translate text="search_AND"}";
    searchJoins["OR"]  = "{translate text="search_OR"}";
    searchJoins["NOT"]  = "{translate text="search_NOT"}";
    var addSearchString = "{translate text="add_search"}";
    var searchLabel    = "{translate text="adv_search_label"}";
    var searchFieldLabel = "{translate text="in"}";
    var deleteSearchGroupString = "{translate text="del_search"}";
    var searchMatch    = "{translate text="search_match"}";
    var searchFormId    = 'advSearchForm';
</script>
{* Step 2: Call the javascript to make use of the above *}
<script type="text/javascript" src="{$path}/services/Search/advanced.js"></script>
{* Step 3: Build the page *}
<script type="text/javascript">
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
