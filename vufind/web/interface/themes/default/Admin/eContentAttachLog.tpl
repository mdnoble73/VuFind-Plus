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
		<h1>eContent Attachment Log</h1>
		
		<div id="econtentAttachLogContainer">
			<table>
				<thead>
					<tr><th>Source Path</th><th>Started</th><th>Finished</th><th>Status</th><th>Files Processed</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->sourcePath}</td>
							<td>{$logEntry->dateStarted|date_format:"%D %T"}</td>
							<td>{$logEntry->dateFinished|date_format:"%D %T"}</td>
							<td>{$logEntry->status}</td>
							<td>{$logEntry->recordsProcessed}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>