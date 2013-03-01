{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>

	<div id="main-content">
	{if $user}
		{if $profile.web_note}
			<div id="web_note">{$profile.web_note}</div>
		{/if}

		<div class="myAccountTitle">{translate text='Available Holds From OverDrive'}</div>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if count($overDriveHolds.available) > 0}
			<div class='sortOptions'>
				Hide Covers <input type="checkbox" onclick="$('.imageColumnOverdrive').toggle();"/>
			</div>

			<div class='holdSectionBody'>
				<table class="myAccountTable">
					<thead>
						<tr><th class='imageColumnOverdrive'></th><th>Title</th><th>Notification Sent</th><th>Expires</th></tr>
					</thead>
					<tbody>
					{foreach from=$overDriveHolds.available item=record}
						<tr>
							<td rowspan="{$record.numRows}" class='imageColumnOverdrive'><img src="{$record.imageUrl}" alt="Cover Image" /></td>
							<td>
								{if $record.recordId != -1}<a href="{$path}/EcontentRecord/{$record.recordId}/Home">{/if}{$record.title}{if $record.recordId != -1}</a>{/if}
								{if $record.subTitle}<br/>{$record.subTitle}{/if}
								{if strlen($record.author) > 0}<br/>by: {$record.author}{/if}
							</td>
							<td>{$record.notificationDate|date_format:"%a $b %e, %Y %I:%M%p"}</td>
							<td>{$record.expirationDate|date_format:"%a $b %e, %Y %I:%M%p"}</td>
							{* Allow borrow and cancel hold *}
							<td>
								<a href="#" onclick="{if overDriveVersion==1}checkoutOverDriveItem{else}checkoutOverDriveItemOneClick{/if}('{$record.overDriveId}','{$record.formatId}')" class="button">Check Out</a>
								<br/><br/>
								<a href="#" class="button" onclick="cancelOverDriveHold('{$record.overDriveId}','{$record.formatId}')">Cancel Hold</a>
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		{else}
			<p>You do not have any holds that are ready for pickup from OverDrive.</p>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
