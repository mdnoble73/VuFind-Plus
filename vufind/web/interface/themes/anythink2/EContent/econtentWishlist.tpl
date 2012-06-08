<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>eContent Records With Wish List</h1>

  <p>A total of {$recordsOnWishList|@count} records have people on the wishlist.</p>
  <div class="exportButton">
  <form action="{$path}" method="get">
  <input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
  </form>
  </div>
  <table>
    <thead>
      <tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>ILS ID</th><th>Source</th><th>Wishlist Size</th></tr>
    </thead>
    {foreach from=$recordsOnWishList item=record}
      <tr>
      <td>{$record->id}</td>
      <td><a href='{$path}/EcontentRecord/{$record->id}/Home'>{$record->title}</a></td>
      <td>{$record->author}</td>
      <td>{$record->isbn}</td>
      <td>{$record->ilsId}</td>
      <td>{$record->source}</td>
      <td>{$record->numWishList}</td>
      </tr>
    {/foreach}
  </table>
</div>
