{strip}
	<div id="page-content" class="content">
		{if $error}<p class="error">{$error}</p>{/if}
		<div id="sidebar">
			<div class="button"><a href="{$path}/MyAccount/Home">Return to Account</a></div>
			<hr />

			{* Report filters *}
			<div class="sidegroup">
				<h4>Report Filters</h4>
				<div class="sidegroupContents">
					<form id="offlineHoldsFilter">
						<div>
							<div>
								<label for="startDate">Start Date</label> <input type="text" name="startDate" id="startDate" size="10" value="{$startDate|date_format:'%m/%d/%Y'}"/>
							</div>
							<div>
								<label for="endDate">End Date</label> <input type="text" name="endDate" id="endDate" size="10" value="{$endDate|date_format:'%m/%d/%Y'}"/>
							</div>
							{*
							<div>
								<label for="typesToInclude">Include</label>
								<select name="typesToInclude" id="statiToInclude">
									<option value="everything" {if $typesToInclude=='everything'}selected="selected"{/if}>Everything</option>
									<option value="checkouts" {if $typesToInclude=='checkouts'}selected="selected"{/if}>Check Outs</option>
									<option value="checkins" {if $typesToInclude=='checkins'}selected="selected"{/if}>Check Ins</option>
								</select>
							</div>
							*}
							<div>
								<label for="loginsToInclude">Logins To Show</label> <input type="text" name="loginsToInclude" id="startDate" size="10" value="{$loginsToInclude}" title="Separate multiple logins with commas, leave blank to include all"/>
							</div>
							<div>
								<label for="hideNotProcessed"><input type="checkbox" name="hideNotProcessed" id="hideNotProcessed" {if $hideNotProcessed}checked="checked"{/if}/> Hide Not Processed</label>
							</div>
							<div>
								<label for="hideFailed"><input type="checkbox" name="hideFailed" id="hideFailed" {if $hideFailed}checked="checked"{/if}/> Hide Failed</label>
							</div>
							<div>
								<label for="hideSuccess"><input type="checkbox" name="hideSuccess" id="hideSuccess" {if $hideSuccess}checked="checked"{/if}/> Hide Successful</label>
							</div>
							<br/>
							<div>
								<input type="submit" name="updateFilters" value="Update Filters"/>
							</div>

						</div>
					</form>
				</div>
			</div>

			{include file="Admin/menu.tpl"}
		</div>

		<div id="main-content">
			<h2>Offline Circulation Summary</h2>
			<table class="citation">
				<tr><th>Total Records</th><td>{$totalRecords}</td></tr>
				<tr><th>Not Processed</th><td>{$totalNotProcessed}</td></tr>
				<tr><th>Passed</th><td>{$totalPassed}</td></tr>
				<tr><th>Failed</th><td>{$totalFailed}</td></tr>
			</table>

			<h2>Offline Circulation</h2>
			{if count($offlineCirculation) > 0}
				<table class="citation">
					<thead>
					<tr><th>#</th><th>Login</th>{*<th>Initials</th><th>Type</th>*}<th>Item Barcode</th><th>Patron Barcode</th><th>Date Entered</th><th>Status</th><th>Notes</th></tr>
					</thead>
					<tbody>
					{foreach from=$offlineCirculation item=offlineCircEntry name='offlinecircs'}
						<tr><td>{$smarty.foreach.offlinecircs.iteration}</td><td>{$offlineCircEntry->login}</td>{*<td>{$offlineCircEntry->initials}</td><td>{$offlineCircEntry->type}</td>*}<td>{$offlineCircEntry->itemBarcode}</td><td>{$offlineCircEntry->patronBarcode}</td><td>{$offlineCircEntry->timeEntered|date_format}</td><td>{$offlineCircEntry->status}</td><td>{$offlineCircEntry->notes}</td></tr>
					{/foreach}
					</tbody>
				</table>
			{else}
				<p>There is no offline circulation information to display.</p>
			{/if}
		</div>
	</div>
	<script	type="text/javascript">
		{literal}
		$(function() {
			$( "#startDate" ).datepicker({ showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$( "#endDate" ).datepicker({ showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
		});
		{/literal}
	</script>
{/strip}