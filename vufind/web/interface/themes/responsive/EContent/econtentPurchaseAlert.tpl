{strip}
	<div id="main-content" class="col-md-12">
		<h2>eContent Purchase Alert</h2>
		
		<p>A total of {$recordsToPurchase|@count} titles should have additional copies purchased.</p>
		<form action="{$path}" method="get">
			<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn btn-sm btn-default">
		</form>

		<table class="table table-striped">
			<thead>
				<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th><th>Total Copies</th><th>Number of Holds</th></tr>
			</thead>
			{foreach from=$recordsToPurchase item=record}
				<tr>
				<td>{$record->id}</td>
				<td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
				<td>{$record->author}</td>
				<td>{$record->isbn}</td>
				<td>{$record->ilsId}</td>
				<td>{$record->source}</td>
				<td>{$record->totalCopies}</td>
				<td>{$record->numHolds}</td>
				</tr>
			{/foreach}
		</table>
	</div>
{/strip}