<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h2>My Materials Requests</h2>
  {if $error}
    <div class="error">{$error}</div>
  {else}
    <div id="materialsRequestFilters">
      Filters:
      <form action="{$path}/MaterialsRequest/MyRequests" method="get">
        <div>
        <div>
          Show:
          <input type="radio" id="openRequests" name="requestsToShow" value="openRequests" {if $showOpen}checked="checked"{/if}/><label for="openRequests">Open Requests</label>
          <input type="radio" id="allRequests" name="requestsToShow" value="allRequests" {if !$showOpen}checked="checked"{/if}/><label for="allRequests">All Requests</label>
        </div>
        <div><input type="submit" name="submit" value="Update Filters"/></div>
        </div>
      </form>
    </div>
    {if count($allRequests) > 0}
      <table id="requestedMaterials" class="tablesorter">
        <thead>
          <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Format</th>
            <th>Status</th>
            <th>Created</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$allRequests item=request}
            <tr>
              <td>{$request->title}</td>
              <td>{$request->author}</td>
              <td>{$request->format}</td>
              <td>{$request->statusLabel|translate}</td>
              <td>{$request->dateCreated|date_format}</td>
              <td>
                <a href="#" onclick='showMaterialsRequestDetails("{$request->id}")' class="button">Details</a>
                {if $request->status == $defaultStatus}
                <a href="#" onclick="return cancelMaterialsRequest('{$request->id}');" class="button">Cancel Request</a>
                {/if}
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    {else}
      <div>There are no materials requests that meet your criteria.</div>
    {/if}
    <div id="createNewMaterialsRequest"><a class="button" href="{$path}/MaterialsRequest/NewRequest">Submit a New Materials Request</a></div>
  {/if}
</div>
<script type="text/javascript">
{literal}
$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 4: {sorter : 'date'}, 5: { sorter: false} } });
{/literal}
</script>
