<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>
<div id="page-content" class="content">
	<div id="sidebar-wrapper"><div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div></div>

	<div id="main-content">
		<h2>Manage Materials Requests</h2>
		{if $error}
			<div class="error">{$error}</div>
		{else}
			<div id="materialsRequestFilters">
				<fieldset>
				<legend>Filters:</legend>
				<form action="{$path}/MaterialsRequest/ManageRequests" method="get">
					<div>
					<div>
						Statuses to Show: <input type="checkbox" name="selectAllStatusFilter" id="selectAllStatusFilter" onclick="$('.statusFilter').attr('checked', $('#selectAllStatusFilter').attr('checked'));"/> <label for="selectAllStatusFilter">Select All</label> <br/>
						<input type="checkbox" name="statusFilter[]" value="pending" {if in_array('pending', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Request pending<br/>
						<input type="checkbox" name="statusFilter[]" value="owned" {if in_array('owned', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Already owned/On order<br/>
						<input type="checkbox" name="statusFilter[]" value="purchased" {if in_array('purchased', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Item purchased, hold placed<br/>
						<input type="checkbox" name="statusFilter[]" value="referredToILL" {if in_array('referredToILL', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Request referred to ILL<br/>
						<input type="checkbox" name="statusFilter[]" value="ILLplaced" {if in_array('ILLplaced', $statusFilter)}checked="checked"{/if} class="statusFilter"/>ILL request placed<br/>
						<input type="checkbox" name="statusFilter[]" value="ILLreturned" {if in_array('ILLreturned', $statusFilter)}checked="checked"{/if} class="statusFilter"/>ILL returned<br/>
						<input type="checkbox" name="statusFilter[]" value="notEnoughInfo" {if in_array('notEnoughInfo', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Not enough info - please contact Collection Development to clarify<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredOutOfPrint" {if in_array('notAcquiredOutOfPrint', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Unable to acquire the item - out of print<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredNotAvailable" {if in_array('notAcquiredNotAvailable', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Unable to acquire the item - not available in the US<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredFormatNotAvailable" {if in_array('notAcquiredFormatNotAvailable', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Unable to acquire the item - format not available<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredPrice" {if in_array('notAcquiredPrice', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Unable to acquire the item - price<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredPublicationDate" {if in_array('notAcquiredPublicationDate', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Unable to acquire the item - publication date<br/>
						<input type="checkbox" name="statusFilter[]" value="requestCancelled" {if in_array('requestCancelled', $statusFilter)}checked="checked"{/if} class="statusFilter"/>Cancelled by Patron<br/>
					</div>
					<div>
						Date: 
						<label for="startDate">From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="8"/>
						<label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="8"/>
					</div>
					<div><input type="submit" name="submit" value="Update Filters"/></div>
					</div>
				</form>
				</fieldset>
			</div>
			{if count($allRequests) > 0}
				<form id="updateRequests" method="get" action="{$path}/MaterialsRequest/ManageRequests">
					<table id="requestedMaterials" class="tablesorter">
						<thead>
							<tr>
								<th><input type="checkbox" name="selectAll" id="selectAll" onclick="$('.select').attr('checked', $('#selectAll').attr('checked'));"/></th>
								<th>Title</th>
								<th>Author</th>
								<th>Format</th>
								<th>ISBN</th>
								<th>UPC</th>
								<th>OCLC Number</th>
								<th>ISSN</th>
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
									<td>{$request->isbn}</td>
									<td>{$request->upc}</td>
									<td>{$request->oclcNumber}</td>
									<td>{$request->issn}</td>
									<td>{$request->status|translate}</td>
									<td>{$request->dateCreated|date_format}</td>
									<td>
										<a href="#" onclick='showMaterialsRequestDetails("{$request->id}")' class="button">Details</a>
										<a href="#" onclick='updateMaterialsRequest("{$request->id}")' class="button">Update&nbsp;Request</a>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
					<div id="materialsRequestActions">
						<div>
							<label for="newStatus">Change status of selected to:</label>
							<select name="newStatus" id="newStatus">
								<option value="pending">Request pending</option>
								<option value="owned">Already owned/On order</option>
								<option value="purchased">Item purchased, hold placed</option>
								<option value="referredToILL">Request referred to ILL</option>
								<option value="ILLplaced">ILL request placed</option>
								<option value="ILLreturned">ILL returned</option>
								<option value="notEnoughInfo">Not enough info - please contact Collection Development to clarify</option>
								<option value="notAcquiredOutOfPrint">Unable to acquire the item - out of print</option>
								<option value="notAcquiredNotAvailable">Unable to acquire the item - not available in the US</option>
								<option value="notAcquiredFormatNotAvailable">Unable to acquire the item - format not available</option>
								<option value="notAcquiredPrice">Unable to acquire the item - price</option>
								<option value="notAcquiredPublicationDate">Unable to acquire the item - publication date</option>
								<option value="requestCancelled">Cancelled by Patron</option>
							</select>
							<input type="submit" name="updateStatus" value="Update Selected Requests" onclick="return updateSelectedRequests();"/>
						</div>
						<div>
							<input type="submit" name="exportSelected" value="Export Selected To Excel" onclick="return exportSelectedRequests();"/>
						</div>
					</div>
				</form>
			{else}
				<div>There are no materials requests that meet your criteria.</div>
			{/if}
		{/if}
	</div>
</div>
<script type="text/javascript">
{literal}
	$("#startDate").datepicker();
	$("#endDate").datepicker();
	$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 5: {sorter : 'date'}, 6: { sorter: false} } });
{/literal}
</script>