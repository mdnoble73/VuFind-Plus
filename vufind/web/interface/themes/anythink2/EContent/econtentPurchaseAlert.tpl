<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>

<div id="main-content">
  <h1>eContent Purchase Alert</h1>

  <p>A total of {$recordsToPurchase|@count} should have additional copies purchased.</p>
  <form action="{$path}" method="get">
  <input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
  </form>
  <table>
    <thead>
      <tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th><th>Total Copies</th><th>Number of Holds</th></tr>
    </thead>
    {foreach from=$recordsToPurchase item=record}
      <tr>
      <td>{$record->id}</td>
      <td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
      <td>{$record->author}</td>
      <td>{$record->isbn}</td>
      <td>{$record->ilsId}</td>
      <td>{$record->source}</td>
      <td>{$record->totalCopies}</td>
      <td>{$record->numHolds}</td>
      </tr>
    {/foreach}
  </table>
</div>
