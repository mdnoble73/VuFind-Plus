<script type="text/javascript" src="{$path}/MaterialsRequest/ajax.js"></script>
<div id="main-content">
	<h2>My Materials Requests</h2>
	{if $error}
		<div class="error">{$error}</div>
	{else}
		<div id="materialsRequestFilters">
			Filters:
			<form action="{$path}/MaterialsRequest/MyRequests" method="get" class="form">
				<div>
					<div>
						Show:
						<label for="openRequests"><input type="radio" id="openRequests" name="requestsToShow" value="openRequests" {if $showOpen}checked="checked"{/if}/> Open Requests</label>
						<label for="allRequests"><input type="radio" id="allRequests" name="requestsToShow" value="allRequests" {if !$showOpen}checked="checked"{/if}/> All Requests</label>
					</div>
					<div><input type="submit" name="submit" value="Update Filters" class="btn btn-sm btn-default"/></div>
				</div>
			</form>
		</div>
		<br/>
		{if count($allRequests) > 0}
			<table id="requestedMaterials" class="table table-striped table-condensed tablesorter">
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
								<a href="#" onclick='VuFind.MaterialsRequest.showMaterialsRequestDetails("{$request->id}")' class="btn btn-info btn-sm">Details</a>
								{if $request->status == $defaultStatus}
								<a href="#" onclick="return VuFind.MaterialsRequest.cancelMaterialsRequest('{$request->id}');" class="btn btn-danger btn-sm">Cancel Request</a>
								{/if}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div>There are no materials requests that meet your criteria.</div>
		{/if}
		<div id="createNewMaterialsRequest"><a href="{$path}/MaterialsRequest/NewRequest" class="btn btn-default btn-sm">Submit a New Materials Request</a></div>
	{/if}
</div>
<script type="text/javascript">
{literal}
$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 4: {sorter : 'date'}, 5: { sorter: false} } });
{/literal}
</script>