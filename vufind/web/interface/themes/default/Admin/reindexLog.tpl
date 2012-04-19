<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Reindex Log</h1>
		
		<div id="econtentAttachLogContainer">
			<table>
				<thead>
					<tr><th>Id</th><th>Started</th><th>Finished</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>