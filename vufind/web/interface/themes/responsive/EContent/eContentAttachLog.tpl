<script type="text/javascript">
{literal}
t=setTimeout("refreshWindow()",30000);
function refreshWindow(){
	window.location.href = window.location.href;
}
{/literal}
</script>
<div id="page-content" class="row-fluid">
	<div id="sidebar" class="span3">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content" class="span9">
		<h3>eContent Attachment Log</h3>
		
		<div id="econtentAttachLogContainer">
			<table>
				<thead>
					<tr><th>Source Path</th><th>Started</th><th>Finished</th><th>Status</th><th>Files Processed</th><th>numErrors</th><th>Notes</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->sourcePath}</td>
							<td>{$logEntry->dateStarted|date_format:"%D %T"}</td>
							<td>{$logEntry->dateFinished|date_format:"%D %T"}</td>
							<td>{$logEntry->status}</td>
							<td>{$logEntry->recordsProcessed}</td>
							<td>{$logEntry->numErrors}</td>
							<td><a href="#" onclick="return showEContentAttachNotes('{$logEntry->id}');">Show Notes</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>{literal}
	function showEContentAttachNotes(id){
		ajaxLightbox("/EContent/AJAX?method=getEContentAttachNotes&id=" + id);
		return false;
	}{/literal}
</script>