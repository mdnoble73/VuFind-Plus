<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>

<div id="main-content" class="col-md-12">
	<h2>Manage Materials Requests</h2>
	{if $error}
		<div class="alert alert-danger">{$error}</div>
	{/if}
	{if $user}
		<div id="materialsRequestFilters" class="accordion">
			<div class="panel panel-default">
			<div class="panel-heading">
				<div class="panel-title collapsed">
					<a href="#filterPanel" data-toggle="collapse" role="button">
						Filters
					</a>
				</div>
			</div>
			<div id="filterPanel" class="panel-collapse collapse">
				<div class="panel-body">

					<form action="{$path}/MaterialsRequest/ManageRequests" method="get">
						<fieldset class="fieldset-collapsible">
							<legend>Statuses to Show:</legend>
							<div class="form-group checkbox">
								<label for="selectAllStatusFilter">
									<input type="checkbox" name="selectAllStatusFilter" id="selectAllStatusFilter" onchange="VuFind.toggleCheckboxes('.statusFilter', '#selectAllStatusFilter');">
									<strong>Select All</strong>
								</label>
							</div>
							<div class="form-group">
								{foreach from=$availableStatuses item=statusLabel key=status}
									<div class="checkbox">
										<label>
											<input type="checkbox" name="statusFilter[]" value="{$status}" {if in_array($status, $statusFilter)}checked="checked"{/if} class="statusFilter">{$statusLabel}
										</label>
									</div>
								{/foreach}
							</div>
						</fieldset>
						<fieldset class="form-group fieldset-collapsible">
							<legend>Date:</legend>
							<div class="form-group">
								<label for="startDate">From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="8">
								<label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="8">
							</div>
						</fieldset>
						<fieldset class="form-group fieldset-collapsible">
							<legend>Format:</legend>
							<div class="form-group checkbox">
								<label for="selectAllFormatFilter">
									<input type="checkbox" name="selectAllFormatFilter" id="selectAllFormatFilter" onchange="VuFind.toggleCheckboxes('.formatFilter', '#selectAllFormatFilter');">
									<strong>Select All</strong>
								</label>
							</div>
							<div class="form-group">
								{foreach from=$availableFormats item=formatLabel key=format}
									<div class="checkbox">
										<label><input type="checkbox" name="formatFilter[]" value="{$format}" {if in_array($format, $formatFilter)}checked="checked"{/if} class="formatFilter">{$formatLabel}</label>
									</div>
								{/foreach}
							</div>
						</fieldset>
						<fieldset class="fieldset-collapsible">
							<legend>Assigned To:</legend>
							<div class="form-group checkbox">
								<label for="showUnassigned">
									<input type="checkbox" name="showUnassigned" id="showUnassigned"{if $showUnassigned} checked{/if}>
									<strong>Unassigned</strong>
								</label>
							</div>
								<div class="form-group checkbox">
								<label for="selectAllAssigneesFilter">
									<input type="checkbox" name="selectAllAssigneesFilter" id="selectAllAssigneesFilter" onchange="VuFind.toggleCheckboxes('.assigneesFilter', '#selectAllAssigneesFilter');">
									<strong>Select All</strong>
								</label>
							</div>
							<div class="form-group">
								{foreach from=$assignees item=displayName key=assigneeId}
{*									<option value="{$assigneeId}">{$displayName}</option>
								{/foreach}

								{foreach from=$availableStatuses item=statusLabel key=status}*}
									<div class="checkbox">
										<label>
											<input type="checkbox" name="assigneesFilter[]" value="{$assigneeId}" {if in_array($assigneeId, $assigneesFilter)}checked="checked"{/if} class="assigneesFilter">{$displayName}
										</label>
									</div>
								{/foreach}

							</div>
						</fieldset>
						<input type="submit" name="submit" value="Update Filters" class="btn btn-default">
					</form>

				</div>
			</div>
		</div>
		{if count($allRequests) > 0}
			<form id="updateRequests" method="post" action="{$path}/MaterialsRequest/ManageRequests" class="form form-horizontal">
				<table id="requestedMaterials" class="table tablesorter table-striped table-hover">
					<thead>
						<tr>
							<th><input type="checkbox" name="selectAll" id="selectAll" onchange="VuFind.toggleCheckboxes('.select', '#selectAll');"></th>
							<th>Id</th>
							<th>Title</th>
							<th>Author</th>
							<th>Format</th>
							<th>Patron</th>
							<th>Hold?</th>
							<th>ILL?</th>
							<th>Assigned To</th>
							<th>Status</th>
							<th>Created</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$allRequests item=request}
							<tr>
								<td><input type="checkbox" name="select[{$request->id}]" class="select"></td>
								<td>{$request->id}</td>
								<td>{$request->title}</td>
								<td>{$request->author}</td>
								<td>{$request->format}</td>
								<td>{$request->lastname}, {$request->firstname}<br>{$request->barcode}</td>
								<td>{if $request->placeHoldWhenAvailable}Yes - {$request->location}{else}No{/if}</td>
								<td>{if $request->illItem}Yes{else}No{/if}</td>
								<td>{$request->assignedTo}</td>
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
				{if $user->hasRole('library_material_requests')}
					<div id="materialsRequestActions">
						<div class="row form-group">
							<div class="col-sm-4">
								<label for="newAssignee" class="control-label">Assign selected to:</label>
							</div>
							<div class="col-sm-8">
								<div class="input-group">
									{if $assignees}
										<select name="newAssignee" id="newAssignee" class="form-control">
											<option value="unselected">Select One</option>

											{foreach from=$assignees item=displayName key=assigneeId}
												<option value="{$assigneeId}">{$displayName}</option>
											{/foreach}

										</select>
										<span class="btn btn-sm btn-primary input-group-addon" onclick="return VuFind.MaterialsRequest.assignSelectedRequests();">Assign Selected Requests</span>
									{else}
										<span class="text-warning">No Valid Assignees Found</span>
									{/if}
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-sm-4">
								<label for="newStatus" class="control-label">Change status of selected to:</label>
							</div>
							<div class="col-sm-8">
								<div class="input-group">
									<select name="newStatus" id="newStatus" class="form-control">
										<option value="unselected">Select One</option>
										{foreach from=$availableStatuses item=statusLabel key=status}
											<option value="{$status}">{$statusLabel}</option>
										{/foreach}
									</select>
									<span class="btn btn-sm btn-primary input-group-addon" onclick="return VuFind.MaterialsRequest.updateSelectedRequests();">Update Selected Requests</span>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-12">
								<input class="btn btn-sm btn-default" type="submit" name="exportSelected" value="Export Selected To Excel" onclick="return VuFind.MaterialsRequest.exportSelectedRequests();">
							</div>
						</div>
					</div>
				{/if}
			</form>
		{else}
			<div class="alert alert-info">There are no materials requests that meet your criteria.</div>
		{/if}
	{/if}
</div>

<script type="text/javascript">
{literal}
$(function () {
	$("#startDate").datepicker();
	$("#endDate").datepicker();
	$("#requestedMaterials").tablesorter({
		cssAsc: 'sortAscHeader',
		cssDesc: 'sortDescHeader',
		cssHeader: 'unsortedHeader',
		widgets: ['zebra', 'filter'],
		headers: { 0: {sorter: false}, 10: {sorter : 'date'}, 11: {sorter: false} }
	});
});
{/literal}
</script>