{if (isset($title)) }
	<script type="text/javascript">
		alert("{$title}");
	</script>
{/if}
<script type="text/javascript">
	{literal}
	$(function() {
			$( "#dateFilterStart" ).datepicker();
		});
	$(function() {
		$( "#dateFilterEnd" ).datepicker();
	});
	{/literal}
</script>
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content">
		{if $user}
			<div class="myAccountTitle">
				<h1>Reports - Page Views By Location</h1>
			</div>
			<form method="get" action="" id="reportForm" class="search">
				<div>
					<div id="filterContainer">
						<div id="filterLeftColumn">
							<div id="startDate">
								Start Date: 
								<input id="dateFilterStart" name="dateFilterStart" value="{$selectedDateStart}" />
							</div>
							<div id="roles">
								Locations: <br/>
								<select id="locationsFilter" name="locationsFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
									{section name=resultsLocationsFilterRow loop=$resultsLocationsFilter} 
											<option value="{$resultsLocationsFilter[resultsLocationsFilterRow].ipId}" {if $resultsLocationsFilter[resultsLocationsFilterRow].ipId|in_array:$selectedLocationsFilter}selected='selected'{/if}>{$resultsLocationsFilter[resultsLocationsFilterRow].location}</option> 
									{/section} 
								</select>
							</div>
						</div>
						<div id="filterRightColumn">
							<div id="endDate">
								End Date: 
								<input id="dateFilterEnd" name="dateFilterEnd" value="{$selectedDateEnd}" />
							</div>
							
							<div class="filterPlaceholder"></div>
						</div>
						<div class="divClear"></div>
						<input type="submit" id="filterSubmit" value="Go" />
					</div>
					
					{if $chartPath}
						<div id="chart">
							<img src="{$chartPath}" alt="Page Views By Location Chart"/>
						</div>
					{/if}
				
					<div id="reportSorting">
						{if $pageLinks.all}
							{translate text="Showing"}
							<b>{$recordStart}</b> - <b>{$recordEnd}</b>
							{translate text='of'} <b>{$recordCount}</b>
							{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
						{/if}
						
						<select name="reportSort" id="reportSort" onchange="this.form.submit();">
							{foreach from=$sortList item=sortListItem key=keyName}
								<option value="{$sortListItem.column}" {if $sortListItem.selected} selected="selected"{/if} >Sort By {$sortListItem.displayName}</option>
							{/foreach}
						</select>
										
						<b>Results Per Page: </b>
						<select name="itemsPerPage" id="itemsPerPage" onchange="this.form.submit();">
							{foreach from=$itemsPerPageList item=itemsPerPageItem key=keyName}
								<option value="{$itemsPerPageItem.amount}" {if $itemsPerPageItem.selected} selected="selected"{/if} >{$itemsPerPageItem.amount}</option>
							{/foreach}
						</select>
					</div>
				
					<table border="0" width="100%" class="datatable">
						<tr>
							<th align="center">Location</th>
							<th align="center">Page Views</th>
							<th align="center">Holds</th>
							<th align="center">Renewals</th>
						</tr>
						{section name=resultsPageViewsRow loop=$resultsPageViews} 
							<tr {if $smarty.section.nr.iteration is odd} bgcolor="#efefef"{/if}> 
									<td> 
											{$resultsPageViews[resultsPageViewsRow].Location}</td> 
									<td>{$resultsPageViews[resultsPageViewsRow].PageViews}
									</td> 
									<td>{$resultsPageViews[resultsPageViewsRow].Holds}
									</td> 
									<td>{$resultsPageViews[resultsPageViewsRow].Renewals}
									</td> 
							</tr> 
						{sectionelse} 
							<tr><td align="center" colspan="5"><br /><b>No Page Views </b> <br /> </td></tr> 
						{/section}
					</table>
				
					{if $pageLinks.all}<div class="pagination" id="pagination-bottom">Page: {$pageLinks.all}</div>{/if}
					
					<div class="exportButton">
						<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" />
					</div>
				</div>
			</form>
		{else}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
</div>