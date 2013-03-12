{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div data-role="page" id="MyResearch-checkedout-overdrive">
	{include file="header.tpl"}

	<div data-role="content">
	{if $user}
		{if $profile.web_note}
			<div id="web_note">{$profile.web_note}</div>
		{/if}

		<h3>Titles available for checkout</h3>
		
		{if count($overDriveHolds.available) > 0}
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
							<p><strong>Available:</strong> {$record.notificationDate|date_format:"%a $b %e, %Y %I:%M%p"}</p>
							<p><strong>Pickup By:</strong> {$record.expirationDate|date_format:"%a $b %e, %Y %I:%M%p"}</p>
						</div>
						{* Allow borrow and cancel hold *}
						{if !empty($record.recordId)}</a>{/if}
						<div data-role="controlgroup">
							<a href="#" onclick="{if overDriveVersion==1}checkoutOverDriveItem{else}checkoutOverDriveItemOneClick{/if}('{$record.overDriveId}','{$record.formatId}')" data-role="button" rel="external" data-ajax="false">Check Out</a>
							<a href="#" data-role="button" rel="external" data-ajax="false" onclick="cancelOverDriveHold('{$record.overDriveId}','{$record.formatId}')">Cancel Hold</a>
						</div>
					</li>
				{/foreach}
				</ul>
			</div>
		{else}
			<p>You do not have any holds that are ready for pickup from OverDrive.</p>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
