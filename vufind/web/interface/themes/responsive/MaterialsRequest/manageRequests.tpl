<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>

<div id="main-content" class="col-md-12">
	<h2>Manage Materials Requests</h2>
	{if $error}
		<div class="error">{$error}</div>
	{else}
		<div id="materialsRequestFilters">
			<fieldset>
			<legend class="collapsible">Filters:</legend>
			<form action="{$path}/MaterialsRequest/ManageRequests" method="get">
				<div>
				<div>
					Statuses to Show: <input type="checkbox" name="selectAllStatusFilter" id="selectAllStatusFilter" onchange="VuFind.toggleCheckboxes('.statusFilter', '#selectAllStatusFilter');"/> <label for="selectAllStatusFilter">Select All</label> <br/>
					{foreach from=$availableStatuses item=statusLabel key=status}
						<input type="checkbox" name="statusFilter[]" value="{$status}" {if in_array($status, $statusFilter)}checked="checked"{/if} class="statusFilter"/>{$statusLabel}<br/>
					{/foreach}
				</div>
				<div>
					Date:
					<label for="startDate">From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="8"/>
					<label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="8"/>
				</div>
				<div>
					Format:<input type="checkbox" name="selectAllFormatFilter" id="selectAllFormatFilter" onchange="VuFind.toggleCheckboxes('.formatFilter', '#selectAllFormatFilter');"/> <label for="selectAllFormatFilter">Select All</label> <br/>
					{foreach from=$availableFormats item=formatLabel key=format}
						<input type="checkbox" name="formatFilter[]" value="{$format}" {if in_array($format, $formatFilter)}checked="checked"{/if} class="formatFilter"/>{$formatLabel}<br/>
					{/foreach}
				</div>
				<div><input type="submit" name="submit" value="Update Filters"/></div>
				</div>
			</form>
			</fieldset>
		</div>
		{if count($allRequests) > 0}
			<form id="updateRequests" method="get" action="{$path}/MaterialsRequest/ManageRequests" class="form form-horizontal">
				<table id="requestedMaterials" class="table tablesorter table-striped table-hover">
					<thead>
						<tr>
							<th><input type="checkbox" name="selectAll" id="selectAll" onchange="VuFind.toggleCheckboxes('.select', '#selectAll');"/></th>
							<th>Title</th>
							<th>Author</th>
							<th>Format</th>
							<th>Patron</th>
							<th>Hold?</th>
							<th>ILL?</th>
							<th>Status</th>
							<th>Created</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$allRequests item=request}
							<tr>
								<td><input type="checkbox" name="select[{$request->id}]" class="select"/></td>
								<td>{$request->title}</td>
								<td>{$request->author}</td>
								<td>{$request->format}</td>
								<td>{$request->lastname}, {$request->firstname}<br/>{$request->barcode}</td>
								<td>{if $request->placeHoldWhenAvailable}Yes - {$request->location}{else}No{/if}</td>
								<td>{if $request->illItem}Yes{else}No{/if}</td>
								<td>{$request->statusLabel|translate}</td>
								<td>{$request->dateCreated|date_format}</td>
								<td>
									<div class="btn-group btn-group-vertical btn-group-sm">
										<a href="#" onclick='VuFind.MaterialsRequest.showMaterialsRequestDetails("{$request->id}")' class="btn btn-sm btn-info">Details</a>
										<a href="#" onclick='VuFind.MaterialsRequest.updateMaterialsRequest("{$request->id}")' class="btn btn-sm btn-primary">Update&nbsp;Request</a>
									</div>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<div id="materialsRequestActions">
					<div class="row">
						<div class="col-sm-4">
							<label for="newStatus">Change status of selected to:</label>
						</div>
						<div class="col-sm-8">
							<div class="input-group">
								<select name="newStatus" id="newStatus" class="form-control">
									<option value="unselected"/>Select One</option>
									{foreach from=$availableStatuses item=statusLabel key=status}
										<option value="{$status}"/>{$statusLabel}</option>
									{/foreach}
								</select>
								<span class="btn btn-sm btn-primary input-group-addon" onclick="return VuFind.MaterialsRequest.updateSelectedRequests();">Update Selected Requests</span>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<input class="btn btn-sm btn-default" type="submit" name="exportSelected" value="Export Selected To Excel" onclick="return VuFind.MaterialsRequest.exportSelectedRequests();"/>
						</div>
					</div>
				</div>
			</form>
		{else}
			<div>There are no materials requests that meet your criteria.</div>
		{/if}
	{/if}
</div>

<script type="text/javascript">
{literal}
	$("#startDate").datepicker();
	$("#endDate").datepicker();
	$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 5: {sorter : 'date'}, 6: { sorter: false} } });
{/literal}
</script>