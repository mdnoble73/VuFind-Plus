{strip}
	<div id="main-content" class="col-md-12">
		<h2>eContent Trial Records To Purchase</h2>
		
		<p>A total of {$trialRecordsToPurchase|@count} records that were loaded on a trial basis should be purchased.</p>
		<table class="table table-striped">
			<thead>
				<tr><th>ID</th><th>Title</th><th>Author</th><th>Source</th><th>Number of Checkouts</th></tr>
			</thead>
			{foreach from=$trialRecordsToPurchase item=record}
				<tr>
				<td>{$record->id}</td>
				<td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
				<td>{$record->author}</td>
				<td>{$record->source}</td>
				<td>{$record->numCheckouts}</td>
				</tr>
			{/foreach}
		</table>
	</div>
{/strip}