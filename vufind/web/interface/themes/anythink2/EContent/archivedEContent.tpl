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
		<h1>Archived eContent</h1>
		
		<div id="filterContainer">
			<form action="{$path}" method="get">
			<div id="filterLeftColumn">
				<div id="startDate">
					Start Date: 
					<input id="dateFilterStart" name="dateFilterStart" value="{$selectedDateStart}" />
				</div>
				
				<div id="sourceFilterContainer">
					Source: <br/> 
					<select id="sourceFilter" name="sourceFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
						{section name=resultsSourceFilterRow loop=$resultsSourceFilter} 
							<option value="{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}" {if !isset($selectedSourceFilter)}selected='selected' {elseif $resultsSourceFilter[resultsSourceFilterRow].SourceValue|in_array:$selectedSourceFilter}selected='selected'{/if}>{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}</option> 
						{/section} 
					</select>
				</div>
			</div>
			<div id="filterRightColumn">
				<div id="endDate">
					End Date: 
					<input id="dateFilterEnd" name="dateFilterEnd" value="{$selectedDateEnd}" />
				</div>
			</div>

			<div class="divClear"></div>
			<input type="submit" value="Update Report"/>
			</form>
		</div>
		
		
 
		<p>A total of {$archivedRecords|@count} archived records were found.</p> 
		
		<div class="exportButton">
		<form action="{$path}" method="get">
		<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
		</form>
		</div>
		
		<table>
			<thead>
				<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th><th>Date Archived</th></tr>
			</thead>
			{foreach from=$archivedRecords item=record}
				<tr>
				<td>{$record->id}</td>
				<td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
				<td>{$record->author}</td>
				<td>{$record->isbn}</td>
				<td>{$record->ilsId}</td>
				<td>{$record->source}</td>
				<td>{$record->date_updated|date_format}</td>
				</tr>
			{/foreach}
		</table>
		

	</div>
</div>