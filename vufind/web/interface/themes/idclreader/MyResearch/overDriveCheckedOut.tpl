{if $user}
	{if count($overDriveCheckedOutItems) > 0}
		<div data-role="collapsible-set" data-theme="a" data-content-theme="c">
			{foreach from=$overDriveCheckedOutItems item=record}
				<div data-role="collapsible">
					<h3>{$record.title}{if $warningDueDate neq ""}&nbsp;<span class='warningDueDate'>({$warningDueDate})</span>{/if}</h3>
					<p>
						<p>
							<strong>Checked Out: </strong>{$record.checkoutdate|date_format}
						</p>
						<p>
							<strong>Expires: </strong>{ $record.expiresOn}
						</p>
						<p>
							<strong>Format: </strong>{$record.format}
						</p>
						<p>
							<ul data-role="listview" data-theme="a" data-inset="true">
	            				<li><a href="/EcontentRecord/{$record.recordId|escape}">View Item Record</a></li>
	            				<li data-role="list-divider" data-divider-theme="b">Access to the eContent</li>
	            				<li>
	            					<a href='{$record.downloadLink}' target='_blank'>Download</a>
	            				</li>
	            			</ul>
	            		</p>
					</p>
				</div>
			{/foreach}
		</div>
	{else}
		<p>You do not have any OverDrive checked out items</p>
	{/if}
{else}
	<p>You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.</p>
{/if}