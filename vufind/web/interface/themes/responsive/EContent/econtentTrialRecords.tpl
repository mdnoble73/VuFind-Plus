<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>
  
	<div id="main-content" class="col-md-9">
		<h1>eContent Trial Records To Purchase</h1>
		
		<p>A total of {$trialRecordsToPurchase|@count} records loaded on a trial basis should be purchased.</p> 
		<table class="table table-bordered table-striped">
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
</div>