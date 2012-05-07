<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Cron Log</h1>
		
		<div id="econtentAttachLogContainer">
			<table class="logEntryDetails" cellspacing="0">
				<thead>
					<tr><th>Id</th><th>Started</th><th>Finished</th><th>Elapsed</th><th>Processes Run</th><th>Had Errors?</th><th>Notes</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td><a href="#" class="collapsed" id="cronEntry{$logEntry->id}" onclick="toggleProcessInfo('{$logEntry->id}');return false;">{$logEntry->id}</a></td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->getNumProcesses()}</td>
							<td>{if $logEntry->getHadErrors()}Yes{else}No{/if}</td>
							<td><a href="#" onclick="return showCronNotes('{$logEntry->id}');">Show Notes</a></td>
						</tr>
						<tr><td colspan="8">
							<div class="logEntryProcessDetails" id="processInfo{$logEntry->id}" style="display:none">
								<table class="logEntryProcessDetails" cellspacing="0">
									<thead>
										<tr><th>Process Name</th><th>Started</th><th>End Time</th><th>Elapsed</th><th>Errors</th><th>Updates</th><th>Notes</th></tr>
									</thead>
									<tbody>
									{foreach from=$logEntry->processes() item=process}
										<tr>
											<td>{$process->processName}</td>
											<td>{$process->startTime|date_format:"%D %T"}</td>
											<td>{$process->endTime|date_format:"%D %T"}</td>
											<td>{$process->getElapsedTime()}</td>
											<td>{$process->numErrors}</td>
											<td>{$process->numUpdates}</td>
											<td><a href="#" onclick="return showCronProcessNotes('{$process->id}');">Show Notes</a></td>
										</tr>
									{/foreach}
									</tbody>
								</table>
							</div>
						</td></tr>
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
	}
	function toggleProcessInfo(id){
		$("#cronEntry" + id).toggleClass("expanded collapsed");
		$("#processInfo" + id).toggle();
	}
	{/literal}
</script>