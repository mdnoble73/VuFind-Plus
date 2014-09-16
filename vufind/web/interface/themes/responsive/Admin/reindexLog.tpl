{strip}
	<div id="main-content" class="col-md-12">
		<h3>Reindex Log</h3>
		
		<div id="econtentAttachLogContainer">
			<table class="logEntryDetails table table-condensed table-hover">
				<thead>
					<tr><th>Id</th><th>Started</th><th>Last Update</th><th>Finished</th><th>Elapsed</th><th>Works Processed</th><th>Lists Processed</th><th>Notes</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td><a href="#" class="collapsed" id="reindexEntry{$logEntry->id}" onclick="VuFind.Admin.toggleReindexProcessInfo('{$logEntry->id}');return false;">{$logEntry->id}</a></td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->numWorksProcessed}</td>
							<td>{$logEntry->numListsProcessed}</td>
							<td><a href="#" onclick="return VuFind.Admin.showReindexNotes('{$logEntry->id}');">Show Notes</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
{/strip}