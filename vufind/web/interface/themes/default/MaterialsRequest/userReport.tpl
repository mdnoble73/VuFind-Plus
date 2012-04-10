<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>
<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="page-content" class="content">
	<div id="sidebar-wrapper"><div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div></div>

	<div id="main-content">
		<h2>Materials Request Requests by User Report</h2>
		{if $error}
			<div class="error">{$error}</div>
		{else}
			<div id="materialsRequestFilters">
				<fieldset>
				<legend>Filters:</legend>
				<form action="{$path}/MaterialsRequest/UserReport" method="get">
					<div>
					<div>
						Statuses to Show: <input type="checkbox" name="selectAllStatusFilter" id="selectAllStatusFilter" onclick="$('.statusFilter').attr('checked', $('#selectAllStatusFilter').attr('checked'));"/> <label for="selectAllStatusFilter">Select All</label> <br/>
						{foreach from=$availableStatuses item=statusLabel key=status}
							<input type="checkbox" name="statusFilter[]" value="{$status}" {if in_array($status, $statusFilter)}checked="checked"{/if} class="statusFilter"/>{$statusLabel}<br/>
						{/foreach}
					</div>
					<div><input type="submit" name="submit" value="Update Filters"/></div>
					</div>
				</form>
				</fieldset>
			</div>
			
			{* Display results in table*}
			<table id="summaryTable" class="tablesorter">
				<thead>
					<tr>
						<th>Last Name</th>
						<th>First Name</th>
						<th>Barcode</th>
						{foreach from=$statuses item=status}
							<th>{$status|translate}</th>
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach from=$userData item=userInfo key=userId}
						<tr>
							<td>{$userInfo.lastName}</td>
							<td>{$userInfo.firstName}</td>
							<td>{$userInfo.barcode}</td>
							{foreach from=$statuses key=status item=statusLabel}
								<th>{if $userInfo.requestsByStatus.$status}{$userInfo.requestsByStatus.$status}{else}0{/if}</th>
							{/foreach}
						</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}
		
		<form action="{$fullPath}" method="get">
			<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
		</form>
		
		{* Export to Excel option *}
	</div>
</div>
<script type="text/javascript">
{literal}
	$("#startDate").datepicker();
	$("#endDate").datepicker();
	$("#summaryTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: 'date'} } });
{/literal}
</script>