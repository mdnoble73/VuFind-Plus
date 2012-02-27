<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>eContent Trial Records To Purchase</h1>
		
		<p>A total of {$trialRecordsToPurchase|@count} records loaded on a trial basis should be purchased.</p> 
		<table>
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