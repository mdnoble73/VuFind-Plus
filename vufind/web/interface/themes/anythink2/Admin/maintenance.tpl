<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>System Maintenance</h1>
  <h2>Utilities</h2>
  {if $status}<div class="warning">{$status}</div>{/if}
  <form method="get">
   <input type="hidden" name="util" value="deleteExpiredSearches">
   Delete unsaved user search histories older than
   <input type="text" name="daysOld" size="5" value="2"> days.<br />
   <input type="submit" name="submit" value="Submit">
  </form>
</div>
