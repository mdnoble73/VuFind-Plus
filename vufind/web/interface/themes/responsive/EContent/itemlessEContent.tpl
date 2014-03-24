{strip}
	<div id="main-content" class="col-sm-12">
		<h2>Itemless eContent</h2>

		<form action="{$path}" method="get">
			<div class="form-group">
				<label for="sourceFilter">Source</label>
				<select id="sourceFilter" name="sourceFilter[]" class="form-control">
					{section name=resultsSourceFilterRow loop=$resultsSourceFilter}
						<option value="{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}" {if !isset($selectedSourceFilter)}selected='selected' {elseif $resultsSourceFilter[resultsSourceFilterRow].SourceValue|in_array:$selectedSourceFilter}selected='selected'{/if}>{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}</option>
					{/section}
				</select>
			</div>
			<div class="form-group">
				<input type="submit" value="Update Report" class="btn btn-sm btn-info"/>
			</div>
		</form>

		<p>A total of {$itemlessRecords|@count} records were found that do not have items attached.</p>
		
		<div class="exportButton">
			<form action="{$path}" method="get">
				<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn btn-sm btn-default">
			</form>
		</div>
		
		<table class="table table-striped">
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
{/strip}