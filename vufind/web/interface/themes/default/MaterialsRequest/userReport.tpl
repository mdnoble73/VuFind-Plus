<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js"></script>
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
				Filters:
				<form action="{$path}/MaterialsRequest/UserReport" method="get">
					<div>
					<div>
						Statuses to Show: <br/>
						<input type="checkbox" name="statusFilter[]" value="pending" {if in_array('pending', $statusFilter)}checked="checked"{/if}/>Request pending<br/>
						<input type="checkbox" name="statusFilter[]" value="owned" {if in_array('owned', $statusFilter)}checked="checked"{/if}/>Already owned/On order<br/>
						<input type="checkbox" name="statusFilter[]" value="purchased" {if in_array('purchased', $statusFilter)}checked="checked"{/if}/>Item purchased, hold placed<br/>
						<input type="checkbox" name="statusFilter[]" value="referredToILL" {if in_array('referredToILL', $statusFilter)}checked="checked"{/if}/>Request referred to ILL<br/>
						<input type="checkbox" name="statusFilter[]" value="ILLplaced" {if in_array('ILLplaced', $statusFilter)}checked="checked"{/if}/>ILL request placed<br/>
						<input type="checkbox" name="statusFilter[]" value="ILLreturned" {if in_array('ILLreturned', $statusFilter)}checked="checked"{/if}/>ILL returned<br/>
						<input type="checkbox" name="statusFilter[]" value="notEnoughInfo" {if in_array('notEnoughInfo', $statusFilter)}checked="checked"{/if}/>Not enough info - please contact Collection Development to clarify<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredOutOfPrint" {if in_array('notAcquiredOutOfPrint', $statusFilter)}checked="checked"{/if}/>Unable to acquire the item - out of print<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredNotAvailable" {if in_array('notAcquiredNotAvailable', $statusFilter)}checked="checked"{/if}/>Unable to acquire the item - not available in the US<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredFormatNotAvailable" {if in_array('notAcquiredFormatNotAvailable', $statusFilter)}checked="checked"{/if}/>Unable to acquire the item - format not available<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredPrice" {if in_array('notAcquiredPrice', $statusFilter)}checked="checked"{/if}/>Unable to acquire the item - price<br/>
						<input type="checkbox" name="statusFilter[]" value="notAcquiredPublicationDate" {if in_array('notAcquiredPublicationDate', $statusFilter)}checked="checked"{/if}/>Unable to acquire the item - publication date<br/>
						<input type="checkbox" name="statusFilter[]" value="requestCancelled" {if in_array('requestCancelled', $statusFilter)}checked="checked"{/if}/>Cancelled by Patron<br/>
					</div>
					<div><input type="submit" name="submit" value="Update Filters"/></div>
					</div>
				</form>
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