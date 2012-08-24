<script type="text/javascript" src="{$url}/services/MyResearch/ajax.js"></script>
<script type="text/javascript" src="{$url}/js/overdrive.js"></script>
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}
	<div data-role="content">
	{if $user}
		{if count($overDriveHolds.available) > 0}
			<h1>Titles available for checkout</h1>
			<div class='holdSectionBody'>
				<ul class="results checkedout-list" data-role="listview">
				{foreach from=$overDriveHolds.available item=record}
					<li>
						{if !empty($record.recordId)}<a rel="external" href="{$path}/EcontentRecord/{$record.recordId|escape}">{/if}
						<div class="result">
						<h3>
							{$record.title}
							{if $record.subTitle}<br/>{$record.subTitle}{/if}
						</h3>
						{if strlen($record.record->author) > 0}<p>by: {$record.record->author}</p>{/if}
						<p><strong>Available: {$record.notificationDate|date_format}</p>
						<p><strong>Pickup By: {$record.expirationDate|date_format}</p>
						</div>
						{if !empty($record.recordId)}</a>{/if}
						<div data-role="controlgroup">
						{foreach from=$record.formats item=format}
							<a href="#" onclick="checkoutOverDriveItem('{$format.overDriveId}','{$format.formatId}')" data-role="button" rel="external">Check&nbsp;Out {$format.name}</a>
						{/foreach}
						</div>
					</li>
				{/foreach}
				</ul>
			</div>
		{/if}
		
		{if count($overDriveHolds.unavailable) > 0}
			<div class='holdSection'>
				<h3>Requested items not yet available</h3>
				<div class='holdSectionBody'>
					<ul class="results checkedout-list" data-role="listview">
						{foreach from=$overDriveHolds.unavailable item=record}
							<li>
								<div class="result">
							<h3>
								{$record.title}
								{if $record.subTitle}<br/>{$record.subTitle}{/if}
							</h3>
							{if strlen($record.record->author) > 0}<p>by: {$record.record->author}</p>{/if}
							<p><strong>Position: {$record.holdQueuePosition} out of {$record.holdQueueLength}</p>
							</div>
							{if !empty($record.recordId)}</a>{/if}
							<div data-role="controlgroup">
								<a href="#" onclick="cancelOverDriveHold('{$record.overDriveId}','{$record.formatId}')" data-role="button" rel="external">Remove</a>
							</div>
						{/foreach}
						</tbody>
					</table>
				</div>
			</div>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
