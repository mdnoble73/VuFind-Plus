<div id="page-content" class="content">
  {if $error}<p class="error">{$error}</p>{/if} 
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}

    {include file="Admin/menu.tpl"}
  </div>
  <div id="main-content">
    <h1>Database Maintenance</h1>
    <div id="maintenanceOptions"></div>
    <form id="dbMaintenance" action="{$path}/Admin/{$action}">
    <div>
    <table>
    <thead>
      <tr>
        <th><input type="checkbox" id="selectAll" onclick="toggleCheckboxes('.selectedUpdate', $('#selectAll').attr('checked'));"/></th>
        <th>Name</th>
        <th>Description</th>
        <th>Already Run?</th>
        {if $showStatus}
        <th>Status</th>
        {/if}
      </tr>
    </thead>
    <tbody>
      {foreach from=$sqlUpdates item=update key=updateKey}
      <tr class="{if $update.alreadyRun}updateRun{else}updateNotRun{/if}" {if $update.alreadyRun}style="display:none"{/if}>
        <td><input type="checkbox" name="selected[{$updateKey}]" {if !$update.alreadyRun}checked="checked"{/if} class="selectedUpdate"/></td>
        <td>{$update.title}</td>
        <td>{$update.description}</td>
        <td>{if $update.alreadyRun}Yes{else}No{/if}</td>
        {if $showStatus}
        <td>{$update.status}</td>
        {/if}
      </tr>
      {/foreach}
    </tbody>
    </table>
    <input type="submit" name="submit" class="button" value="Run Selected Updates"/>
    <input type="checkbox" name="hideUpdatesThatWereRun" id="hideUpdatesThatWereRun" checked="checked" onclick="$('.updateRun').toggle();"><label for="hideUpdatesThatWereRun">Hide updates that have been run</label>
    </div>
    </form>
    
  </div>
</div>