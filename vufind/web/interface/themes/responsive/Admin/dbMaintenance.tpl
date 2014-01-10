{strip}
<div id="page-content" class="row">
	{if $error}<p class="error">{$error}</p>{/if} 
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}

		{include file="Admin/menu.tpl"}
	</div>
	<div id="main-content" class="col-md-9">
		<h3>Database Maintenance</h3>
		<div id="maintenanceOptions"></div>
		<form id="dbMaintenance" action="{$path}/Admin/{$action}">
			<div>
				<table class="table table-hover table-condensed table-bordered">
				<thead>
					<tr>
						<th>
							<label for="selectAll" class="checkbox">
								<input type="checkbox" id="selectAll" onclick="toggleCheckboxes('.selectedUpdate', $('#selectAll').attr('checked'));" checked="checked"/>
								&nbsp;Name
							</label>
						</th>
						<th>Description</th>
						<th>Already Run?</th>
						{if $showStatus}
						<th>Status</th>
						{/if}
					</tr>
				</thead>
				<tbody>
					{foreach from=$sqlUpdates item=update key=updateKey}
					<tr class="{if $update.alreadyRun}updateRun{else}updateNotRun{/if}" {if $update.alreadyRun}style="display:none"{/if}>
						<td>
							<label for="{$updateKey}" class="checkbox">
								<input type="checkbox" name="selected[{$updateKey}]" id="{$updateKey}" {if !$update.alreadyRun}checked="checked"{/if} class="selectedUpdate"/>
								&nbsp;{$update.title}
							</label>
						</td>
						<td>{$update.description}</td>
						<td>{if $update.alreadyRun}Yes{else}No{/if}</td>
						{if $showStatus}
						<td>{$update.status}</td>
						{/if}
					</tr>
					{/foreach}
				</tbody>
				</table>
				<input type="submit" name="submit" class="btn btn-primary" value="Run Selected Updates"/>
				<label for="hideUpdatesThatWereRun" class="checkbox">
					<input type="checkbox" name="hideUpdatesThatWereRun" id="hideUpdatesThatWereRun" checked="checked" onclick="$('.updateRun').toggle();"/>Hide updates that have been run
				</label>
			</div>
		</form>
		
	</div>
</div>
{/strip}