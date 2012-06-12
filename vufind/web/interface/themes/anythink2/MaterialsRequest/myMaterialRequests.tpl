<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>My Requests <img src="http://www.oclc.org/developer/sites/default/files/badges/wc_badge1.png" width="88" height="31" alt="Some library data on this site is provided by WorldCat, the world's largest library catalog [WorldCat.org]" /></h1>
  <p>View all of your requests and their statuses below. </p>
  <p>Questions about your requests? Contact our Collection Development team at <a href="mailto:requests@anythinklibraries.org">requests@anythinklibraries.org</a> or call 303-405-3293.</p>
  {if $error}
    <div class="error">{$error}</div>
  {else}
    {if count($allRequests) > 0}
      <div id="materialsRequestFilters">
        <form action="{$path}/MaterialsRequest/MyRequests" method="get">
            <div>
              <div><input type="radio" id="openRequests" name="requestsToShow" value="openRequests" {if $showOpen}checked="checked"{/if}/>&nbsp;<label for="openRequests">Show open requests</label></div>
              <div><input type="radio" id="allRequests" name="requestsToShow" value="allRequests" {if !$showOpen}checked="checked"{/if}/>&nbsp;<label for="allRequests">Show all requests</label></div>
            <input type="submit" name="submit" value="Update"/></div>
        </form>
      </div>
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
                <a href="#" onclick='showMaterialsRequestDetailsAnythink("{$request->id}")' class="button">Details</a>
                {if $request->status == $defaultStatus}
                <a href="#" onclick="return cancelMaterialsRequest('{$request->id}');" class="button">Cancel Request</a>
                {/if}
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    {else}
      <h3>There are no materials requests that meet your criteria.</h3>
    {/if}
    <div id="createNewMaterialsRequest"><a class="button" href="{$path}/MaterialsRequest/NewRequest">Submit a New Request</a></div>
  {/if}
</div>
<script type="text/javascript">
{literal}
$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 4: {sorter : 'date'}, 5: { sorter: false} } });
{/literal}
</script>
