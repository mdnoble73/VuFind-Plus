<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>Reindex Log</h1>
  <div id="econtentAttachLogContainer">
    <table class="logEntryDetails" cellspacing="0">
      <thead>
        <tr><th>Id</th><th>Started</th><th>Finished</th><th>Elapsed</th><th>Processes Run</th><th>Had Errors?</th></tr>
      </thead>
      <tbody>
        {foreach from=$logEntries item=logEntry}
          <tr>
            <td><a href="#" class="collapsed" id="reindexEntry{$logEntry->id}" onclick="toggleProcessInfo('{$logEntry->id}');return false;">{$logEntry->id}</a></td>
            <td>{$logEntry->startTime|date_format:"%D %T"}</td>
            <td>{$logEntry->endTime|date_format:"%D %T"}</td>
            <td>{$logEntry->getElapsedTime()}</td>
            <td>{$logEntry->getNumProcesses()}</td>
            <td>{if $logEntry->getHadErrors()}Yes{else}No{/if}</td>
          </tr>
          <tr class="logEntryProcessDetails" id="processInfo{$logEntry->id}" style="display:none">
            <td colspan="6" >
              <table class="logEntryProcessDetails" cellspacing="0">
                <thead>
                  <tr><th>Process Name</th><th>Records Processed</th><th>eContent Records Processed</th><th>Resources Processed</th><th>Errors</th><th>Added</th><th>Updated</th><th>Deleted</th><th>Skipped</th><th>Notes</th></tr>
                </thead>
                <tbody>
                {foreach from=$logEntry->processes() item=process}
                  <tr><td>{$process->processName}</td><td>{$process->recordsProcessed}</td><td>{$process->eContentRecordsProcessed}</td><td>{$process->resourcesProcessed}</td><td>{$process->numErrors}</td><td>{$process->numAdded}</td><td>{$process->numUpdated}</td><td>{$process->numDeleted}</td><td>{$process->numSkipped}</td><td><a href="#" onclick="return showReindexProcessNotes('{$process->id}');">Show Notes</a></td></tr>
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
<script>{literal}
  function showReindexProcessNotes(id){
    ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id);
    return false;
  }
  function toggleProcessInfo(id){
    $("#reindexEntry" + id).toggleClass("expanded collapsed");
    $("#processInfo" + id).toggle();
  }{/literal}
</script>
