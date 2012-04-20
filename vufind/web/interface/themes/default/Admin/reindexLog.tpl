<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Reindex Log</h1>
		
		<div id="econtentAttachLogContainer">
			<table class="logEntryDetails">
				<thead>
					<tr><th>Id</th><th>Started</th><th>Finished</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
						</tr>
						<tr colspan="3" id="processInfo{$logEntry->id}">
							<table class="logEntryProcessDetails">
								<thead>
									<tr><th>Process Name</th><th>Records Processed</th><th>eContent Records Processed</th><th>Resources Processed</th><th>Errors</th><th>Added</th><th>Updated</th><th>Deleted</th><th>Skipped</th><th>Notes</th></tr>
								</thead>
								<tbody>
								{foreach from=$logEntry->processes() item=process}
									<tr><td>{$process->processName}</td><td>{$process->recordsProcessed}</td><td>{$process->eContentRecordsProcessed}</td><td>{$process->resourcesProcessed}</td><td>{$process->numErrors}</td><td>{$process->numAdded}</td><td>{$process->numUpdated}</td><td>{$process->numDeleted}</td><td>{$process->numSkipped}</td><td><a href="#" onclick="return showReindexProcessNotes('{$process->id}');">Show Notes</a></td></tr>
								{/foreach}
								</tbody>
							</table>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>{literal}
	function showReindexProcessNotes(id){
		ajaxLightbox("/Admin/AJAX?method=getProcessNotes&id=" + id);
		return false;
	}{/literal}
</script>