{strip}
	<div id="main-content" class="col-md-12">
		{if $user}
			<h1>Student Report</h1>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form class="form form-inline">
				<label for="selectedReport" class="control-label">Available Reports</label>
				<select name="selectedReport" id="selectedReport" class="form-control input-sm">
					{foreach from=$availableReports item=curReport key=reportLocation}
						<option value="{$reportLocation}" {if $reportLocation==$selectedReport}selected="selected"{/if}>{$curReport}</option>
					{/foreach}
				</select>
				&nbsp;
				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
			</form>

			{if $reportData}
				<br/>
				<table class="table table-striped table-condensed">
					{foreach from=$reportData item=dataRow}
						<tr>
							{foreach from=$dataRow item=dataCell}
								<td>{$dataCell}</td>
							{/foreach}
						</tr>
					{/foreach}
				</table>
			{/if}
		{else}
			You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
		{/if}
	</div>
{/strip}