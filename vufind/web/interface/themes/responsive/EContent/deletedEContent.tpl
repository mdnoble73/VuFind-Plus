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

{strip}
	<div id="main-content" class="col-md-12">
		<h2>Deleted eContent</h2>
		
		<div id="filterContainer">
			<form action="{$path}" method="get">
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							<label for="dateFilterStart">Start Date</label>
							<input id="dateFilterStart" name="dateFilterStart" value="{$selectedDateStart}" class="form-control"/>
						</div>
						<div class="form-group">
							<label for="dateFilterEnd">End Date</label>
							<input id="dateFilterEnd" name="dateFilterEnd" value="{$selectedDateEnd}" class="form-control"/>
						</div>
					</div>
					<div class="col-sm-4">
						<label for="sourceFilter">Source</label>
						<select id="sourceFilter" name="sourceFilter[]" multiple="multiple" size="5" class="form-control">
							{section name=resultsSourceFilterRow loop=$resultsSourceFilter}
								<option value="{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}" {if !isset($selectedSourceFilter)}selected='selected' {elseif $resultsSourceFilter[resultsSourceFilterRow].SourceValue|in_array:$selectedSourceFilter}selected='selected'{/if}>{$resultsSourceFilter[resultsSourceFilterRow].SourceValue}</option>
							{/section}
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<input type="submit" value="Update Report" class="btn btn-sm btn-info"/>
					</div>
				</div>
			</form>
		</div>
		
		<p>A total of {$deletedRecords|@count} deleted records were found.</p>
		
		<div class="exportButton">
			<form action="{$path}" method="get">
				<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn btn-sm btn-default">
			</form>
		</div>
		
		<table class="table table-striped">
			<thead>
				<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th><th>Date Deleted</th></tr>
			</thead>
			{foreach from=$deletedRecords item=record}
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
{/strip}