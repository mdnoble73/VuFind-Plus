<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>
  
	<div id="main-content" class="col-md-9">
		<h1>Itemless eContent</h1>
		
		<div id="filterContainer">
			<form action="{$path}" method="get">
			Source: 
			<select id="sourceFilter" name="sourceFilter[]" class="multiSelectFilter">
				{section name=resultsSourceFilterRow loop=$resultsSourceFilter} 
					<option value="{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}" {if !isset($selectedSourceFilter)}selected='selected' {elseif $resultsSourceFilter[resultsSourceFilterRow].SourceValue|in_array:$selectedSourceFilter}selected='selected'{/if}>{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}</option> 
				{/section} 
			</select>
			<input type="submit" value="Update Report"/>
			</form>
		</div>
 
		<p>A total of {$itemlessRecords|@count} itemless records were found.</p> 
		
		<div class="exportButton">
		<form action="{$path}" method="get">
		<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn">
		</form>
		</div>
		
		<table class="table table-bordered table-striped">
			<thead>
				<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th></tr>
			</thead>
			{foreach from=$itemlessRecords item=record}
				<tr>
				<td>{$record->id}</td>
				<td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
				<td>{$record->author}</td>
				<td>{$record->isbn}</td>
				<td>{$record->ilsId}</td>
				<td>{$record->source}</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>