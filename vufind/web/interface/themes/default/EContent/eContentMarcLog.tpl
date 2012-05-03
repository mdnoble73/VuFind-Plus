<script type="text/javascript">
{literal}
t=setTimeout("refreshWindow()",30000);
function refreshWindow(){
	window.location.href = window.location.href;
}
{/literal}
</script>
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>eContent Marc Record Log</h1>
		
		<div id="marcImportLogContainer">
			<table>
				<thead>
					<tr><th>Source</th><th>Filename</th><th>Supplemental Filename</th><th>Access Type</th><th>Import Started</th><th>Import Finished</th><th>Status</th><th>Records Processed</th><th>Records With Errors</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->source}</td>
							<td>{$logEntry->filename}</td>
							<td>{$logEntry->supplementalFilename}</td>
							<td>{$logEntry->accessType}</td>
							<td>{$logEntry->dateStarted|date_format:"%D %T"|replace:' ':'&nbsp;'}</td>
							<td>{$logEntry->dateFinished|date_format:"%D %T"|replace:' ':'&nbsp;'}</td>
							<td>{$logEntry->status}</td>
							<td>{$logEntry->recordsProcessed}</td>
							<td>{if $logEntry->recordsWithErrors > 0}<a href="#" onclick="showElementInLightbox('Marc Record Import Errors', '#errorsPopup{$logEntry->id}');return false;">{/if}{$logEntry->recordsWithErrors}{if $logEntry->recordsWithErrors > 0}</a><div id="errorsPopup{$logEntry->id}" class="errorsPopup" style="display:none">{$logEntry->errors|replace:"\r\n":"<br/>"}</div>{/if}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>