{strip}
<div id="page-content" class="row-fluid">
	<div id="sidebar" class="span3">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content" class="span9">
		<h3>Reindex Log</h3>
		
		<div id="econtentAttachLogContainer">
			<table class="logEntryDetails table table-bordered table-condensed table-hover">
				<thead>
					<tr><th>Id</th><th>Started</th><th>Last Update</th><th>Finished</th><th>Elapsed</th><th>Processes Run</th><th>Had Errors?</th><th>Notes</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td><a href="#" class="collapsed" id="reindexEntry{$logEntry->id}" onclick="toggleReindexProcessInfo('{$logEntry->id}');return false;">{$logEntry->id}</a></td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->getNumProcesses()}</td>
							<td>{if $logEntry->getHadErrors()}Yes{else}No{/if}</td>
							<td><a href="#" onclick="return showReindexNotes('{$logEntry->id}');">Show Notes</a></td>
						</tr>
						<tr class="logEntryProcessDetails" id="processInfo{$logEntry->id}" style="display:none">
							<td colspan="8" >
								<table class="logEntryProcessDetails">
									<thead>
										<tr><th>Process Name</th><th>Print Marc Records Processed</th><th>eContent Marc Records Processed</th><th>Non-Marc OverDrive Records Processed</th><th>Resources Processed</th><th>Errors</th><th>Added</th><th>Updated</th><th>Deleted</th><th>Skipped</th><th>Notes</th></tr>
									</thead>
									<tbody>
									{foreach from=$logEntry->processes() item=process}
										<tr><td>{$process->processName}</td><td>{$process->recordsProcessed}</td><td>{$process->eContentRecordsProcessed}</td><td>{$process->overDriveNonMarcRecordsProcessed}</td><td>{$process->resourcesProcessed}</td><td>{$process->numErrors}</td><td>{$process->numAdded}</td><td>{$process->numUpdated}</td><td>{$process->numDeleted}</td><td>{$process->numSkipped}</td><td><a href="#" onclick="return showReindexProcessNotes('{$process->id}');">Show Notes</a></td></tr>
									{/foreach}
									</tbody>
								</table>
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
<script type="text/javascript" src="/js/admin.js" />
{/strip}