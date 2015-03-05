{strip}
	<div id="main-content" class="col-md-12">
		<h2>eContent Records on Patron Wish Lists</h2>
		
		<p>A total of {$recordsOnWishList|@count} records have people on the wish list.</p>
		<div class="exportButton">
			<form action="{$path}" method="get">
				<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn btn-sm btn-default">
			</form>
		</div>
		<table class="table table-striped">
			<thead>
				<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th><th>Wishlist Size</th></tr>
			</thead>
			{foreach from=$recordsOnWishList item=record}
				<tr>
				<td>{$record->id}</td>
				<td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
				<td>{$record->author}</td>
				<td>{$record->isbn}</td>
				<td>{$record->ilsId}</td>
				<td>{$record->source}</td>
				<td>{$record->numWishList}</td>
				</tr>
			{/foreach}
		</table>
	</div>
{/strip}