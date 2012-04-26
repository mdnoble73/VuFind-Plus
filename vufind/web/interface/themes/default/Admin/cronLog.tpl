<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Cron Log</h1>
		
		<div id="econtentAttachLogContainer">
			<table class="logEntryDetails">
				<thead>
					<tr><th>Id</th><th>Started</th><th>Last Updated</th><th>Finished</th><th>Elapsed</th><th>Notes</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td><a href="#" onclick="return showCronNotes('{$logEntry->id}');">Show Notes</a></td>
						</tr>
						<tr colspan="3" id="processInfo{$logEntry->id}">
							<table class="logEntryProcessDetails">
								<thead>
									<tr><th>Process Name</th><th>Started</th><th>Last Updated</th><th>End Time</th><th>Errors</th><th>Updates</th><th>Notes</th></tr>
								</thead>
								<tbody>
								{foreach from=$logEntry->processes() item=process}
									<tr>
										<td>{$process->processName}</td>
										<td>{$process->startTime|date_format:"%D %T"}</td>
										<td>{$process->lastUpdate|date_format:"%D %T"}</td>
										<td>{$process->endTime|date_format:"%D %T"}</td>
										<td>{$process->getElapsedTime()}</td>
										<td>{$process->numErrors}</td>
										<td>{$process->numUpdates}</td>
										<td><a href="#" onclick="return showCronProcessNotes('{$process->id}');">Show Notes</a></td>
									</tr>
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
		ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id);
		return false;
	}
	function showCronNotes(id){
		ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id);
		return false;
	}
	function showCronProcessNotes(id){
		ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id);
		return false;
	}{/literal}
</script>