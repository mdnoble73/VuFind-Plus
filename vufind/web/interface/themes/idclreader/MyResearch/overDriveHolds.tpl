<script type="text/javascript" src="{$url}/js/overdrive.js"></script>
{if $user}	
	{if count($overDriveHolds.available) > 0 || count($overDriveHolds.unavailable) > 0}
	
		<div data-role="collapsible-set" data-theme="a" data-content-theme="c">
			{if count($overDriveHolds.available) > 0}
				<h3>Available Holds</h3>
				{foreach from=$overDriveHolds.available item=record}
					<div data-role="collapsible">
						<h3>{$record.title}</h3>
						
					</div>
				{/foreach}
			{/if}
			
			
			{if count($overDriveHolds.unavailable) > 0}
				<h3>Unavailable Holds</h3>
				{foreach from=$overDriveHolds.unavailable item=record}
					<div data-role="collapsible">
						<h3>{$record.title}</h3>
						<p>
							<p>
								<strong>Hold Position: </strong>{$record.holdQueuePosition} out of {$record.holdQueueLength}
							</p>
							<p>
								<ul data-role="listview" data-theme="a" data-inset="true">
	            					<li data-role="list-divider" data-divider-theme="b">Actions</li>
	            					<li><a href="/EcontentRecord/{$record.recordId|escape}">View Item Record</a></li>
	            					<li><a href="#" onclick="cancelOverDriveHold('{$record.overDriveId}','{$record.formatId}')" >Cancel</a></li>
	            				</ul>
						</p>
					</div>
				{/foreach}
			{/if}
		</div>
	{else}
		<p>You do not have any OrverDrive hold item</p>
	{/if}
{else}
	<p>You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.</p>
{/if}