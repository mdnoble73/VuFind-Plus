<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content" class="col-md-9">
		<h1>eContent Purchase Alert</h1>
		
		<p>A total of {$recordsToPurchase|@count} titles should have additional copies purchased.</p>
		<form action="{$path}" method="get">
		<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn">
		</form>
		<table class="table table-bordered table-striped">
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
</div>